import { buildMaps } from '@/hooks/useElementMaps';
import type {
  SimpleElementNode,
  ColumnNode,
  RowNode,
  SectionNode,
  ElementTreeResponse,
} from '@/types/elements';

// --- Test factories ---

function makeElement(id: number, parentId: number): SimpleElementNode {
  return {
    id,
    parentId,
    title: `Element ${id}`,
    blockSchema: {
      typeName: 'Element',
      label: 'Element',
      type: 'Element',
      title: '',
      summary: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
  };
}

function makeColumn(
  id: number,
  children: SimpleElementNode[],
  parentId: number,
): ColumnNode {
  return {
    id,
    parentId,
    title: `Column ${id}`,
    blockSchema: {
      typeName: 'Column',
      label: 'Column',
      type: 'Column',
      title: '',
      summary: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'column',
    allowedTypes: null,
    children,
    gridSettings: { md: { width: 6, offset: 0, visible: true } },
  };
}

function makeRow(
  id: number,
  children: ColumnNode[],
  parentId: number,
): RowNode {
  return {
    id,
    parentId,
    title: `Row ${id}`,
    blockSchema: {
      typeName: 'Row',
      label: 'Row',
      type: 'Row',
      title: '',
      summary: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'row',
    allowedTypes: null,
    children,
  };
}

function makeSection(
  id: number,
  children: RowNode[],
  parentId: number,
): SectionNode {
  return {
    id,
    parentId,
    title: `Section ${id}`,
    blockSchema: {
      typeName: 'Section',
      label: 'Section',
      type: 'Section',
      title: '',
      summary: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'section',
    allowedTypes: null,
    children,
  };
}

describe('buildMaps', () => {
  const tree: ElementTreeResponse = {
    '42': [
      makeSection(1, [
        makeRow(10, [
          makeColumn(20, [makeElement(30, 20), makeElement(31, 20)], 10),
          makeColumn(21, [makeElement(32, 21)], 10),
        ], 1),
      ], 42),
    ],
  };

  it('populates nodeMap with every node in the tree', () => {
    const { nodeMap } = buildMaps(tree);

    expect(nodeMap.size).toBe(7); // section, row, 2 columns, 3 elements
    expect(nodeMap.get(1)?.id).toBe(1);
    expect(nodeMap.get(10)?.id).toBe(10);
    expect(nodeMap.get(20)?.id).toBe(20);
    expect(nodeMap.get(21)?.id).toBe(21);
    expect(nodeMap.get(30)?.id).toBe(30);
    expect(nodeMap.get(31)?.id).toBe(31);
    expect(nodeMap.get(32)?.id).toBe(32);
  });

  it('includes root area arrays in childrenByParentId', () => {
    const { childrenByParentId } = buildMaps(tree);

    expect(childrenByParentId.has(42)).toBe(true);
    expect(childrenByParentId.get(42)).toBe(tree['42']);
  });

  it('includes container id entries in childrenByParentId', () => {
    const { childrenByParentId } = buildMaps(tree);

    // Section id=1 → [row]
    expect(childrenByParentId.has(1)).toBe(true);
    expect(childrenByParentId.get(1)!.map((n) => n.id)).toEqual([10]);

    // Row id=10 → [column 20, column 21]
    expect(childrenByParentId.has(10)).toBe(true);
    expect(childrenByParentId.get(10)!.map((n) => n.id)).toEqual([20, 21]);

    // Column 20 id=20 → [element 30, element 31]
    expect(childrenByParentId.has(20)).toBe(true);
    expect(childrenByParentId.get(20)!.map((n) => n.id)).toEqual([30, 31]);

    // Column 21 id=21 → [element 32]
    expect(childrenByParentId.has(21)).toBe(true);
    expect(childrenByParentId.get(21)!.map((n) => n.id)).toEqual([32]);
  });

  it('returns empty maps for an empty tree', () => {
    const { nodeMap, childrenByParentId } = buildMaps({});

    expect(nodeMap.size).toBe(0);
    expect(childrenByParentId.size).toBe(0);
  });

  it('returns null for non-existent node IDs', () => {
    const { nodeMap } = buildMaps(tree);

    expect(nodeMap.get(999)).toBeUndefined();
  });

  it('handles multiple root areas', () => {
    const multiAreaTree: ElementTreeResponse = {
      '42': [makeSection(1, [], 42)],
      '99': [makeSection(2, [], 99)],
    };

    const { nodeMap, childrenByParentId } = buildMaps(multiAreaTree);

    expect(nodeMap.size).toBe(2);
    expect(childrenByParentId.has(42)).toBe(true);
    expect(childrenByParentId.has(99)).toBe(true);
    expect(childrenByParentId.has(1)).toBe(true);
    expect(childrenByParentId.has(2)).toBe(true);
  });
});
