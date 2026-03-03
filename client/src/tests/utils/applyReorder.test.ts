import { applyReorder } from '@/utils/applyReorder';
import type {
  SimpleElementNode,
  ColumnNode,
  RowNode,
  SectionNode,
  ElementTreeResponse,
} from '@/types/elements';

// --- Test factories ---

function makeElement(id: number): SimpleElementNode {
  return {
    id,
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
): ColumnNode {
  return {
    id,
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
): RowNode {
  return {
    id,
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
): SectionNode {
  return {
    id,
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

describe('applyReorder', () => {
  describe('same-parent reorder', () => {
    it('moves an element after another element in the same container', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11), makeElement(12)], 200),
        ],
      };

      // Move element 10 after element 11 (within area 200)
      const result = applyReorder(tree, 10, 200, 11);

      const children = (result['100'][0] as ColumnNode).children!;
      expect(children.map((c) => c.id)).toEqual([11, 10, 12]);
    });

    it('moves an element to the start when afterElementId is null', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11), makeElement(12)], 200),
        ],
      };

      // Move element 12 to the start of area 200
      const result = applyReorder(tree, 12, 200, null);

      const children = (result['100'][0] as ColumnNode).children!;
      expect(children.map((c) => c.id)).toEqual([12, 10, 11]);
    });

    it('moves an element to the end', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11), makeElement(12)], 200),
        ],
      };

      // Move element 10 after element 12 (end of area 200)
      const result = applyReorder(tree, 10, 200, 12);

      const children = (result['100'][0] as ColumnNode).children!;
      expect(children.map((c) => c.id)).toEqual([11, 12, 10]);
    });
  });

  describe('cross-parent move', () => {
    it('moves an element from one column to another', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11)], 200),
          makeColumn(2, [makeElement(20)], 300),
        ],
      };

      // Move element 10 from area 200 to area 300, after element 20
      const result = applyReorder(tree, 10, 300, 20);

      const col1 = result['100'][0] as ColumnNode;
      const col2 = result['100'][1] as ColumnNode;
      expect(col1.children!.map((c) => c.id)).toEqual([11]);
      expect(col2.children!.map((c) => c.id)).toEqual([20, 10]);
    });

    it('moves an element to an empty container', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10)], 200),
          makeColumn(2, [], 300),
        ],
      };

      // Move element 10 to empty area 300 (null = at start)
      const result = applyReorder(tree, 10, 300, null);

      const col1 = result['100'][0] as ColumnNode;
      const col2 = result['100'][1] as ColumnNode;
      expect(col1.children!.map((c) => c.id)).toEqual([]);
      expect(col2.children!.map((c) => c.id)).toEqual([10]);
    });
  });

  describe('section reorder within root area', () => {
    it('reorders sections in the root area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [], 200),
          makeSection(2, [], 300),
          makeSection(3, [], 400),
        ],
      };

      // Move section 1 after section 2 in root area 100
      const result = applyReorder(tree, 1, 100, 2);

      expect(result['100'].map((n) => n.id)).toEqual([2, 1, 3]);
    });

    it('moves a section to the start of the root area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [], 200),
          makeSection(2, [], 300),
          makeSection(3, [], 400),
        ],
      };

      // Move section 3 to the start of root area 100
      const result = applyReorder(tree, 3, 100, null);

      expect(result['100'].map((n) => n.id)).toEqual([3, 1, 2]);
    });
  });

  describe('nested tree operations', () => {
    it('moves a row between sections', () => {
      const tree: ElementTreeResponse = {
        '1': [
          makeSection(10, [makeRow(100, [], 1000)], 500),
          makeSection(20, [makeRow(200, [], 2000)], 600),
        ],
      };

      // Move row 100 from section 10's area (500) to section 20's area (600) after row 200
      const result = applyReorder(tree, 100, 600, 200);

      const sec1 = result['1'][0] as SectionNode;
      const sec2 = result['1'][1] as SectionNode;
      expect(sec1.children!.map((r) => r.id)).toEqual([]);
      expect(sec2.children!.map((r) => r.id)).toEqual([200, 100]);
    });

    it('finds elements deep in the tree', () => {
      const tree: ElementTreeResponse = {
        '1': [
          makeSection(
            10,
            [
              makeRow(
                100,
                [
                  makeColumn(1000, [makeElement(50), makeElement(51)], 5000),
                  makeColumn(1001, [makeElement(60)], 6000),
                ],
                4000,
              ),
            ],
            3000,
          ),
        ],
      };

      // Move element 50 from column 1000's area (5000) to column 1001's area (6000) after element 60
      const result = applyReorder(tree, 50, 6000, 60);

      const section = result['1'][0] as SectionNode;
      const row = section.children![0] as RowNode;
      const col1 = row.children![0] as ColumnNode;
      const col2 = row.children![1] as ColumnNode;
      expect(col1.children!.map((e) => e.id)).toEqual([51]);
      expect(col2.children!.map((e) => e.id)).toEqual([60, 50]);
    });
  });

  describe('no-op detection', () => {
    it('returns the same reference when element is already at the target position', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11), makeElement(12)], 200),
        ],
      };

      // Element 11 is already after element 10 in area 200
      const result = applyReorder(tree, 11, 200, 10);

      expect(result).toBe(tree);
    });

    it('returns the same reference when moving to start and already at start', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11)], 200),
        ],
      };

      // Element 10 is already at the start of area 200
      const result = applyReorder(tree, 10, 200, null);

      expect(result).toBe(tree);
    });

    it('returns the same reference for root area no-op', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [], 200),
          makeSection(2, [], 300),
        ],
      };

      // Section 2 is already after section 1 in root area 100
      const result = applyReorder(tree, 2, 100, 1);

      expect(result).toBe(tree);
    });
  });

  describe('immutability', () => {
    it('does not mutate the original tree', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11)], 200),
        ],
      };

      const originalChildren = [...(tree['100'][0] as ColumnNode).children!];
      applyReorder(tree, 11, 200, null);

      expect((tree['100'][0] as ColumnNode).children!.map((c) => c.id)).toEqual(
        originalChildren.map((c) => c.id),
      );
    });

    it('preserves references to unaffected branches', () => {
      const unaffectedSection = makeSection(99, [], 9000);
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10), makeElement(11)], 200),
        ],
        '999': [unaffectedSection],
      };

      const result = applyReorder(tree, 11, 200, null);

      // The unaffected root area array should be the same reference
      expect(result['999']).toBe(tree['999']);
    });
  });

  describe('edge cases', () => {
    it('returns the same tree when element is not found', () => {
      const tree: ElementTreeResponse = {
        '100': [makeElement(10)],
      };

      const result = applyReorder(tree, 999, 100, null);

      expect(result).toBe(tree);
    });

    it('returns the same tree when target area is not found', () => {
      const tree: ElementTreeResponse = {
        '100': [makeElement(10)],
      };

      const result = applyReorder(tree, 10, 999, null);

      expect(result).toBe(tree);
    });
  });
});
