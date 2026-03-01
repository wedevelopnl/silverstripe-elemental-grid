import {
  blockSchemaSchema,
  simpleElementNodeSchema,
  columnNodeSchema,
  rowNodeSchema,
  sectionNodeSchema,
  elementNodeSchema,
  elementTreeResponseSchema,
  isContainerNode,
  isSectionNode,
  isRowNode,
  isColumnNode,
  isSimpleElementNode,
} from '@/types/elements';
import type { ColumnNode } from '@/types/elements';

// --- Test fixtures ---

const validBlockSchema = {
  typeName: String.raw`SilverStripe\ElementalGrid\Model\ElementContent`,
  label: 'Content',
  actions: { edit: '/admin/elemental-grid/api/edit/1' },
  content: '<p>Hello world</p>',
};

function makeSimpleNode(overrides: Record<string, unknown> = {}) {
  return {
    id: 1,
    title: 'Text block',
    blockSchema: validBlockSchema,
    obsoleteClassName: null,
    version: 3,
    canDelete: true,
    canPublish: true,
    canUnpublish: true,
    canCreate: true,
    statusFlags: {},
    ...overrides,
  };
}

function makeColumnNode(
  children: unknown[] | null = null,
  overrides: Record<string, unknown> = {},
) {
  return {
    ...makeSimpleNode(),
    id: 10,
    title: 'Column',
    containerType: 'column',
    allowedTypes: { 'App\\Model\\ElementContent': 'Content' },
    children,
    gridSettings: {
      xs: { width: 12, offset: 0, visible: true },
      sm: { width: 12, offset: 0, visible: true },
      md: { width: 12, offset: 0, visible: true },
      lg: { width: 12, offset: 0, visible: true },
      xl: { width: 12, offset: 0, visible: true },
    },
    ...overrides,
  };
}

function makeRowNode(
  children: unknown[] | null = null,
  overrides: Record<string, unknown> = {},
) {
  return {
    ...makeSimpleNode(),
    id: 20,
    title: 'Row',
    containerType: 'row',
    allowedTypes: null,
    children,
    ...overrides,
  };
}

function makeSectionNode(
  children: unknown[] | null = null,
  overrides: Record<string, unknown> = {},
) {
  return {
    ...makeSimpleNode(),
    id: 30,
    title: 'Section',
    containerType: 'section',
    allowedTypes: null,
    children,
    ...overrides,
  };
}

// --- blockSchemaSchema ---

describe('blockSchemaSchema', () => {
  it('parses a valid block schema', () => {
    expect(blockSchemaSchema.parse(validBlockSchema)).toEqual(validBlockSchema);
  });

  it('rejects a block schema missing typeName', () => {
    expect(() =>
      blockSchemaSchema.parse({ actions: { edit: '/edit' }, content: '' }),
    ).toThrow();
  });
});

// --- simpleElementNodeSchema ---

describe('simpleElementNodeSchema', () => {
  it('parses a valid simple element', () => {
    const node = makeSimpleNode();
    expect(simpleElementNodeSchema.parse(node)).toEqual(node);
  });

  it('rejects a node with missing required field (id)', () => {
    const { id: _, ...noId } = makeSimpleNode();
    expect(() => simpleElementNodeSchema.parse(noId)).toThrow();
  });

  it('rejects a node with wrong field type (id as string)', () => {
    expect(() =>
      simpleElementNodeSchema.parse(makeSimpleNode({ id: 'abc' })),
    ).toThrow();
  });

  it('rejects a node with empty title', () => {
    expect(() => simpleElementNodeSchema.parse(makeSimpleNode({ title: '' }))).toThrow();
  });
});

// --- Container node schemas ---

describe('columnNodeSchema', () => {
  it('parses a column with simple element children', () => {
    const column = makeColumnNode([makeSimpleNode()]);
    expect(columnNodeSchema.parse(column).children).toHaveLength(1);
  });

  it('parses a column with null children', () => {
    expect(columnNodeSchema.parse(makeColumnNode(null)).children).toBeNull();
  });

  it('parses a column with empty children', () => {
    expect(columnNodeSchema.parse(makeColumnNode([])).children).toEqual([]);
  });

  it('rejects a column with empty title', () => {
    expect(() => columnNodeSchema.parse(makeColumnNode([], { title: '' }))).toThrow();
  });

  it('accepts row children via passthrough (extra keys not rejected)', () => {
    // With passthrough on simpleElementNodeSchema, a row object has all base
    // fields and passes through — containerType/children are extra keys.
    // Hierarchy enforcement happens at the application level, not schema level.
    const column = makeColumnNode([makeRowNode()]);
    const result = columnNodeSchema.parse(column);
    expect(result.children).toHaveLength(1);
  });

  it('parses column node with gridSettings', () => {
    const input = {
      id: 3,
      title: 'Left Column',
      containerType: 'column',
      allowedTypes: null,
      children: [],
      gridSettings: {
        xs: { width: 12, offset: 0, visible: true },
        md: { width: 6, offset: 0, visible: true },
      },
      blockSchema: { typeName: 'Column', label: 'Column', actions: { edit: '/edit/3' }, content: '' },
      obsoleteClassName: null,
      version: 1,
      canDelete: true,
      canPublish: true,
      canUnpublish: false,
      canCreate: true,
      statusFlags: {},
    };

    const result = columnNodeSchema.parse(input);
    expect(result.gridSettings).toEqual(input.gridSettings);
  });
});

describe('rowNodeSchema', () => {
  it('parses a row with column children', () => {
    const row = makeRowNode([makeColumnNode([makeSimpleNode()])]);
    expect(rowNodeSchema.parse(row).children).toHaveLength(1);
  });

  it('rejects a row with empty title', () => {
    expect(() => rowNodeSchema.parse(makeRowNode([], { title: '' }))).toThrow();
  });

  it('parses a row with null children', () => {
    expect(rowNodeSchema.parse(makeRowNode(null)).children).toBeNull();
  });

  it('rejects a row with simple element children', () => {
    const row = makeRowNode([makeSimpleNode()]);
    expect(() => rowNodeSchema.parse(row)).toThrow();
  });
});

describe('sectionNodeSchema', () => {
  it('parses a section with row children', () => {
    const section = makeSectionNode([makeRowNode([makeColumnNode()])]);
    expect(sectionNodeSchema.parse(section).children).toHaveLength(1);
  });

  it('rejects a section with empty title', () => {
    expect(() => sectionNodeSchema.parse(makeSectionNode([], { title: '' }))).toThrow();
  });

  it('parses a section with null children', () => {
    expect(
      sectionNodeSchema.parse(makeSectionNode(null)).children,
    ).toBeNull();
  });

  it('rejects a section with column children', () => {
    const section = makeSectionNode([makeColumnNode()]);
    expect(() => sectionNodeSchema.parse(section)).toThrow();
  });
});

// --- Extensions field ---

describe('extensions field', () => {
  it('parses a node with extensions data', () => {
    const node = makeSimpleNode({
      extensions: { gridSettings: { span: 6, offset: 0 } },
    });
    const result = simpleElementNodeSchema.parse(node);
    expect(result.extensions).toEqual({
      gridSettings: { span: 6, offset: 0 },
    });
  });

  it('parses a node without extensions key', () => {
    const node = makeSimpleNode();
    const result = simpleElementNodeSchema.parse(node);
    expect(result.extensions).toBeUndefined();
  });

  it('accepts extra keys on simple element via passthrough', () => {
    const node = makeSimpleNode({ futureField: 'hello' });
    const result = simpleElementNodeSchema.parse(node);
    expect((result as Record<string, unknown>).futureField).toBe('hello');
  });
});

// --- elementNodeSchema (union) ---

describe('elementNodeSchema', () => {
  it('parses a simple element', () => {
    const result = elementNodeSchema.parse(makeSimpleNode());
    expect(result.id).toBe(1);
  });

  it('parses a section', () => {
    const result = elementNodeSchema.parse(makeSectionNode());
    expect('containerType' in result && result.containerType).toBe('section');
  });

  it('parses a row', () => {
    const result = elementNodeSchema.parse(makeRowNode());
    expect('containerType' in result && result.containerType).toBe('row');
  });

  it('parses a column', () => {
    const result = elementNodeSchema.parse(makeColumnNode());
    expect('containerType' in result && result.containerType).toBe('column');
  });

  it('parses a full nested tree', () => {
    const tree = makeSectionNode([
      makeRowNode([
        makeColumnNode([makeSimpleNode(), makeSimpleNode({ id: 2 })]),
        makeColumnNode([], { id: 11 }),
      ]),
    ]);
    const result = elementNodeSchema.parse(tree);
    expect('containerType' in result && result.containerType).toBe('section');
  });
});

// --- elementTreeResponseSchema ---

describe('elementTreeResponseSchema', () => {
  it('parses a record of element arrays', () => {
    const response = {
      '42': [makeSectionNode([makeRowNode([makeColumnNode()])])],
      '99': [makeSimpleNode()],
    };
    const result = elementTreeResponseSchema.parse(response);
    expect(Object.keys(result)).toEqual(['42', '99']);
  });

  it('parses an empty record', () => {
    expect(elementTreeResponseSchema.parse({})).toEqual({});
  });
});

// --- Type guards ---

describe('type guards', () => {
  const simple = simpleElementNodeSchema.parse(makeSimpleNode());
  const column = columnNodeSchema.parse(makeColumnNode());
  const row = rowNodeSchema.parse(makeRowNode());
  const section = sectionNodeSchema.parse(makeSectionNode());

  describe('isContainerNode', () => {
    it('returns true for container nodes', () => {
      expect(isContainerNode(section)).toBe(true);
      expect(isContainerNode(row)).toBe(true);
      expect(isContainerNode(column)).toBe(true);
    });

    it('returns false for simple elements', () => {
      expect(isContainerNode(simple)).toBe(false);
    });
  });

  describe('isSectionNode', () => {
    it('returns true only for sections', () => {
      expect(isSectionNode(section)).toBe(true);
      expect(isSectionNode(row)).toBe(false);
      expect(isSectionNode(column)).toBe(false);
      expect(isSectionNode(simple)).toBe(false);
    });
  });

  describe('isRowNode', () => {
    it('returns true only for rows', () => {
      expect(isRowNode(row)).toBe(true);
      expect(isRowNode(section)).toBe(false);
      expect(isRowNode(column)).toBe(false);
      expect(isRowNode(simple)).toBe(false);
    });
  });

  describe('isColumnNode', () => {
    it('returns true only for columns', () => {
      expect(isColumnNode(column)).toBe(true);
      expect(isColumnNode(section)).toBe(false);
      expect(isColumnNode(row)).toBe(false);
      expect(isColumnNode(simple)).toBe(false);
    });
  });

  describe('isSimpleElementNode', () => {
    it('returns true only for simple elements', () => {
      expect(isSimpleElementNode(simple)).toBe(true);
      expect(isSimpleElementNode(section)).toBe(false);
      expect(isSimpleElementNode(row)).toBe(false);
      expect(isSimpleElementNode(column)).toBe(false);
    });
  });

  describe('type narrowing', () => {
    it('narrows to SectionNode with correct children type', () => {
      if (isSectionNode(section)) {
        // TypeScript sees section.children as RowNode[] | null
        const children = section.children;
        expect(children).toBeNull();
      }
    });

    it('narrows to ColumnNode with correct children type', () => {
      const col: ColumnNode = columnNodeSchema.parse(
        makeColumnNode([makeSimpleNode()]),
      );
      if (isColumnNode(col)) {
        // TypeScript sees col.children as SimpleElementNode[] | null
        expect(col.children).toHaveLength(1);
      }
    });
  });
});
