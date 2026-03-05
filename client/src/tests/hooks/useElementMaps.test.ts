import { buildMaps } from '@/hooks/useElementMaps';
import type {
  SimpleElementNode,
  ColumnNode,
  RowNode,
  SectionNode,
  ElementTreeResponse,
} from '@/types/elements';

// --- Test factories ---

function makeElement(id: number, parentAreaId: number): SimpleElementNode {
  return {
    id,
    parentAreaId,
    title: `Element ${id}`,
    blockSchema: {
      typeName: 'Element',
      label: 'Element',
      actions: { edit: `/edit/${id}` },
      content: '',
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
  childAreaId: number,
  parentAreaId: number,
): ColumnNode {
  return {
    id,
    parentAreaId,
    title: `Column ${id}`,
    blockSchema: {
      typeName: 'Column',
      label: 'Column',
      actions: { edit: `/edit/${id}` },
      content: '',
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
    childAreaId,
    gridSettings: { md: { width: 6, offset: 0, visible: true } },
  };
}

function makeRow(
  id: number,
  children: ColumnNode[],
  childAreaId: number,
  parentAreaId: number,
): RowNode {
  return {
    id,
    parentAreaId,
    title: `Row ${id}`,
    blockSchema: {
      typeName: 'Row',
      label: 'Row',
      actions: { edit: `/edit/${id}` },
      content: '',
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
    childAreaId,
  };
}

function makeSection(
  id: number,
  children: RowNode[],
  childAreaId: number,
  parentAreaId: number,
): SectionNode {
  return {
    id,
    parentAreaId,
    title: `Section ${id}`,
    blockSchema: {
      typeName: 'Section',
      label: 'Section',
      actions: { edit: `/edit/${id}` },
      content: '',
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
    childAreaId,
  };
}

describe('buildMaps', () => {
  const tree: ElementTreeResponse = {
    '42': [
      makeSection(1, [
        makeRow(10, [
          makeColumn(20, [makeElement(30, 300), makeElement(31, 300)], 300, 200),
          makeColumn(21, [makeElement(32, 301)], 301, 200),
        ], 200, 100),
      ], 100, 42),
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

  it('includes root area arrays in childrenByAreaId', () => {
    const { childrenByAreaId } = buildMaps(tree);

    expect(childrenByAreaId.has(42)).toBe(true);
    expect(childrenByAreaId.get(42)).toBe(tree['42']);
  });

  it('includes container childAreaId entries in childrenByAreaId', () => {
    const { childrenByAreaId } = buildMaps(tree);

    // Section's childAreaId=100 → [row]
    expect(childrenByAreaId.has(100)).toBe(true);
    expect(childrenByAreaId.get(100)!.map((n) => n.id)).toEqual([10]);

    // Row's childAreaId=200 → [column 20, column 21]
    expect(childrenByAreaId.has(200)).toBe(true);
    expect(childrenByAreaId.get(200)!.map((n) => n.id)).toEqual([20, 21]);

    // Column 20's childAreaId=300 → [element 30, element 31]
    expect(childrenByAreaId.has(300)).toBe(true);
    expect(childrenByAreaId.get(300)!.map((n) => n.id)).toEqual([30, 31]);

    // Column 21's childAreaId=301 → [element 32]
    expect(childrenByAreaId.has(301)).toBe(true);
    expect(childrenByAreaId.get(301)!.map((n) => n.id)).toEqual([32]);
  });

  it('returns empty maps for an empty tree', () => {
    const { nodeMap, childrenByAreaId } = buildMaps({});

    expect(nodeMap.size).toBe(0);
    expect(childrenByAreaId.size).toBe(0);
  });

  it('returns null for non-existent node IDs', () => {
    const { nodeMap } = buildMaps(tree);

    expect(nodeMap.get(999)).toBeUndefined();
  });

  it('handles multiple root areas', () => {
    const multiAreaTree: ElementTreeResponse = {
      '42': [makeSection(1, [], 100, 42)],
      '99': [makeSection(2, [], 200, 99)],
    };

    const { nodeMap, childrenByAreaId } = buildMaps(multiAreaTree);

    expect(nodeMap.size).toBe(2);
    expect(childrenByAreaId.has(42)).toBe(true);
    expect(childrenByAreaId.has(99)).toBe(true);
    expect(childrenByAreaId.has(100)).toBe(true);
    expect(childrenByAreaId.has(200)).toBe(true);
  });
});
