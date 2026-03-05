import { applyReorder } from '@/utils/applyReorder';
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

describe('applyReorder', () => {
  describe('same-parent reorder', () => {
    it('moves an element after another element in the same container', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200), makeElement(12, 200)], 200, 100),
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
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200), makeElement(12, 200)], 200, 100),
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
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200), makeElement(12, 200)], 200, 100),
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
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
          makeColumn(2, [makeElement(20, 300)], 300, 100),
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
          makeColumn(1, [makeElement(10, 200)], 200, 100),
          makeColumn(2, [], 300, 100),
        ],
      };

      // Move element 10 to empty area 300 (null = at start)
      const result = applyReorder(tree, 10, 300, null);

      const col1 = result['100'][0] as ColumnNode;
      const col2 = result['100'][1] as ColumnNode;
      expect(col1.children!.map((c) => c.id)).toEqual([]);
      expect(col2.children!.map((c) => c.id)).toEqual([10]);
    });

    it('updates parentAreaId on the moved element', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200)], 200, 100),
          makeColumn(2, [makeElement(20, 300)], 300, 100),
        ],
      };

      const result = applyReorder(tree, 10, 300, 20);

      const col2 = result['100'][1] as ColumnNode;
      const movedElement = col2.children!.find((c) => c.id === 10)!;
      expect(movedElement.parentAreaId).toBe(300);
    });
  });

  describe('section reorder within root area', () => {
    it('reorders sections in the root area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [], 200, 100),
          makeSection(2, [], 300, 100),
          makeSection(3, [], 400, 100),
        ],
      };

      // Move section 1 after section 2 in root area 100
      const result = applyReorder(tree, 1, 100, 2);

      expect(result['100'].map((n) => n.id)).toEqual([2, 1, 3]);
    });

    it('moves a section to the start of the root area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [], 200, 100),
          makeSection(2, [], 300, 100),
          makeSection(3, [], 400, 100),
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
          makeSection(10, [makeRow(100, [], 1000, 500)], 500, 1),
          makeSection(20, [makeRow(200, [], 2000, 600)], 600, 1),
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
                  makeColumn(1000, [makeElement(50, 5000), makeElement(51, 5000)], 5000, 4000),
                  makeColumn(1001, [makeElement(60, 6000)], 6000, 4000),
                ],
                4000,
                3000,
              ),
            ],
            3000,
            1,
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
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200), makeElement(12, 200)], 200, 100),
        ],
      };

      // Element 11 is already after element 10 in area 200
      const result = applyReorder(tree, 11, 200, 10);

      expect(result).toBe(tree);
    });

    it('returns the same reference when moving to start and already at start', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
        ],
      };

      // Element 10 is already at the start of area 200
      const result = applyReorder(tree, 10, 200, null);

      expect(result).toBe(tree);
    });

    it('returns the same reference for root area no-op', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [], 200, 100),
          makeSection(2, [], 300, 100),
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
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
        ],
      };

      const originalChildren = [...(tree['100'][0] as ColumnNode).children!];
      applyReorder(tree, 11, 200, null);

      expect((tree['100'][0] as ColumnNode).children!.map((c) => c.id)).toEqual(
        originalChildren.map((c) => c.id),
      );
    });

    it('preserves references to unaffected branches', () => {
      const unaffectedSection = makeSection(99, [], 9000, 999);
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
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
        '100': [makeElement(10, 100)],
      };

      const result = applyReorder(tree, 999, 100, null);

      expect(result).toBe(tree);
    });

    it('returns the same tree when target area is not found', () => {
      const tree: ElementTreeResponse = {
        '100': [makeElement(10, 100)],
      };

      const result = applyReorder(tree, 10, 999, null);

      expect(result).toBe(tree);
    });

    it('appends to end when afterElementId is not found in target area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
          makeColumn(2, [makeElement(20, 300)], 300, 100),
        ],
      };

      // Move element 10 to area 300 after a nonexistent element (999)
      const result = applyReorder(tree, 10, 300, 999);

      const col2 = result['100'][1] as ColumnNode;
      // Should fall back to appending at end
      expect(col2.children!.map((c) => c.id)).toEqual([20, 10]);
    });

    it('handles target area that only exists as a container childAreaId', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [makeRow(10, [], 500, 200)], 200, 100),
          makeElement(50, 100),
        ],
      };

      // Area 500 is not a root key, it's row 10's childAreaId
      const result = applyReorder(tree, 50, 500, null);

      const section = result['100'][0] as SectionNode;
      const row = section.children![0] as RowNode;
      // Element 50 should have been inserted into row 10's child area
      expect(row.children!.map((c) => c.id)).toContain(50);
      // Element 50 should no longer be in the root area
      expect(result['100'].map((n) => n.id)).not.toContain(50);
    });
  });

  describe('isAreaAffected — reference preservation', () => {
    it('marks root area as affected when source element is at root level', () => {
      const tree: ElementTreeResponse = {
        '100': [makeSection(1, [], 200, 100), makeSection(2, [], 300, 100)],
        '999': [makeSection(99, [], 9000, 999)],
      };

      // Move section 1 after section 2 within root area 100
      const result = applyReorder(tree, 1, 100, 2);

      // Root area 100 is affected (source is root-level, areaKey matches)
      expect(result['100']).not.toBe(tree['100']);
      // Root area 999 is unaffected
      expect(result['999']).toBe(tree['999']);
    });

    it('marks root area as affected when target area is a nested container', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(
            1,
            [makeRow(10, [makeColumn(100, [makeElement(50, 5000)], 5000, 2000)], 2000, 1000)],
            1000,
            100,
          ),
        ],
        '200': [makeElement(60, 200)],
      };

      // Move element 60 from root area 200 into nested container area 5000
      const result = applyReorder(tree, 60, 5000, null);

      // Root area 100 is affected (contains the target area 5000)
      expect(result['100']).not.toBe(tree['100']);
      // Root area 200 is affected (source is root-level in area 200)
      expect(result['200']).not.toBe(tree['200']);
    });

    it('marks root area as affected when source is in nested container', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(
            1,
            [makeRow(10, [makeColumn(100, [makeElement(50, 5000), makeElement(51, 5000)], 5000, 2000)], 2000, 1000)],
            1000,
            100,
          ),
        ],
        '200': [makeElement(60, 200)],
      };

      // Move element 50 from nested area 5000 to root area 200
      const result = applyReorder(tree, 50, 200, 60);

      // Root area 100 is affected (contains the source area 5000)
      expect(result['100']).not.toBe(tree['100']);
      // Root area 200 is affected (direct target)
      expect(result['200']).not.toBe(tree['200']);
    });

    it('does not affect root areas unrelated to source or target', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
          makeColumn(2, [makeElement(20, 300)], 300, 100),
        ],
        '400': [makeSection(40, [], 4000, 400)],
        '500': [makeElement(50, 500)],
      };

      // Move within root area 100's containers
      const result = applyReorder(tree, 10, 300, 20);

      expect(result['100']).not.toBe(tree['100']);
      expect(result['400']).toBe(tree['400']);
      expect(result['500']).toBe(tree['500']);
    });
  });

  describe('no-op detection — additional cases', () => {
    it('is not a no-op when afterElementId does not exist in target area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200)], 200, 100),
        ],
      };

      // afterElementId 999 doesn't exist — isNoOp should return false
      const result = applyReorder(tree, 10, 200, 999);

      // Not a no-op, so tree should be modified (element appended at end as fallback)
      expect(result).not.toBe(tree);
    });

    it('detects no-op for element at the end of its container', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200), makeElement(11, 200), makeElement(12, 200)], 200, 100),
        ],
      };

      // Element 12 is already after element 11 in area 200
      const result = applyReorder(tree, 12, 200, 11);

      expect(result).toBe(tree);
    });

    it('is not a no-op when element is in a different area', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeColumn(1, [makeElement(10, 200)], 200, 100),
          makeColumn(2, [makeElement(20, 300)], 300, 100),
        ],
      };

      // Move element 10 from area 200 to area 300, even though it would be first
      const result = applyReorder(tree, 10, 300, null);

      expect(result).not.toBe(tree);
    });
  });

  describe('removal from nested containers', () => {
    it('removes element from a deeply nested container during cross-area move', () => {
      const tree: ElementTreeResponse = {
        '1': [
          makeSection(
            10,
            [
              makeRow(
                100,
                [
                  makeColumn(1000, [makeElement(50, 5000), makeElement(51, 5000)], 5000, 4000),
                ],
                4000,
                3000,
              ),
            ],
            3000,
            1,
          ),
          makeSection(20, [makeRow(200, [makeColumn(2000, [], 6000, 4500)], 4500, 3500)], 3500, 1),
        ],
      };

      // Move element 50 from area 5000 to area 6000
      const result = applyReorder(tree, 50, 6000, null);

      const sec1 = result['1'][0] as SectionNode;
      const row1 = sec1.children![0] as RowNode;
      const col1 = row1.children![0] as ColumnNode;
      // Element 50 removed from source
      expect(col1.children!.map((e) => e.id)).toEqual([51]);

      const sec2 = result['1'][1] as SectionNode;
      const row2 = sec2.children![0] as RowNode;
      const col2 = row2.children![0] as ColumnNode;
      // Element 50 inserted into target
      expect(col2.children!.map((e) => e.id)).toEqual([50]);
    });
  });
});
