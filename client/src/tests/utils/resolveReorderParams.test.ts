import { resolveReorderParams } from '@/utils/resolveReorderParams';
import type { ReorderContext } from '@/utils/resolveReorderParams';

describe('resolveReorderParams', () => {
  describe('basic resolution', () => {
    it('resolves a move to the start of a container (index 0)', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 0,
        containerItems: ['element-10', 'element-11', 'element-12'],
        sourceContainerParentId: 200,
        sourceIndex: 1,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 10,
        targetParentId: 200,
        afterElementID: null,
      });
    });

    it('resolves a move after an element', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 2,
        containerItems: ['element-11', 'element-12', 'element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 10,
        targetParentId: 200,
        afterElementID: 12,
      });
    });

    it('resolves a move to the end of a container', () => {
      const context: ReorderContext = {
        activeId: 'row-5',
        overContainerParentId: 100,
        overIndex: 3,
        containerItems: ['row-1', 'row-2', 'row-3', 'row-5'],
        sourceContainerParentId: 100,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 5,
        targetParentId: 100,
        afterElementID: 3,
      });
    });
  });

  describe('cross-container moves', () => {
    it('resolves a cross-container move to the start', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 300,
        overIndex: 0,
        containerItems: ['element-10', 'element-20'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 10,
        targetParentId: 300,
        afterElementID: null,
      });
    });

    it('resolves a cross-container move to a specific position', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 300,
        overIndex: 1,
        containerItems: ['element-20', 'element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 10,
        targetParentId: 300,
        afterElementID: 20,
      });
    });
  });

  describe('active item skipping in same-container moves', () => {
    it('skips the active item when it appears before the insertion point', () => {
      // Active item (element-10) is at index 0, moving to index 2
      // containerItems represents the final order: ['element-11', 'element-12', 'element-10']
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 2,
        containerItems: ['element-11', 'element-12', 'element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      // afterElementID should be element-12 (previous item, not the active item)
      expect(result).toEqual({
        elementID: 10,
        targetParentId: 200,
        afterElementID: 12,
      });
    });
  });

  describe('null returns for invalid input', () => {
    it('returns null for an unparseable activeId', () => {
      const context: ReorderContext = {
        activeId: 'invalid',
        overContainerParentId: 200,
        overIndex: 0,
        containerItems: [],
        sourceContainerParentId: 100,
        sourceIndex: 0,
      };

      expect(resolveReorderParams(context)).toBeNull();
    });

    it('returns null for an empty activeId', () => {
      const context: ReorderContext = {
        activeId: '',
        overContainerParentId: 200,
        overIndex: 0,
        containerItems: [],
        sourceContainerParentId: 100,
        sourceIndex: 0,
      };

      expect(resolveReorderParams(context)).toBeNull();
    });
  });

  describe('no-op detection', () => {
    it('returns null when same container and same index', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 1,
        containerItems: ['element-11', 'element-10', 'element-12'],
        sourceContainerParentId: 200,
        sourceIndex: 1,
      };

      expect(resolveReorderParams(context)).toBeNull();
    });

    it('does not return null when containers differ even if index matches', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 300,
        overIndex: 1,
        containerItems: ['element-20', 'element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 1,
      };

      expect(resolveReorderParams(context)).not.toBeNull();
    });
  });

  describe('different draggable types', () => {
    it('handles section draggable IDs', () => {
      const context: ReorderContext = {
        activeId: 'section-5',
        overContainerParentId: 100,
        overIndex: 1,
        containerItems: ['section-3', 'section-5'],
        sourceContainerParentId: 100,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 5,
        targetParentId: 100,
        afterElementID: 3,
      });
    });

    it('handles column draggable IDs', () => {
      const context: ReorderContext = {
        activeId: 'column-7',
        overContainerParentId: 400,
        overIndex: 0,
        containerItems: ['column-7', 'column-8'],
        sourceContainerParentId: 400,
        sourceIndex: 1,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 7,
        targetParentId: 400,
        afterElementID: null,
      });
    });
  });

  describe('edge cases', () => {
    it('handles a container with only the active item', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 300,
        overIndex: 0,
        containerItems: ['element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 10,
        targetParentId: 300,
        afterElementID: null,
      });
    });

    it('handles afterElementId from an unparseable item gracefully', () => {
      // If the item before the target is somehow invalid, fall back to null
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 1,
        containerItems: ['bad-id', 'element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      // 'bad-id' is not parseable (bad is not a valid type), so afterElementID should be null
      expect(result).toEqual({
        elementID: 10,
        targetParentId: 200,
        afterElementID: null,
      });
    });
  });

  describe('no-op guard boundary conditions', () => {
    it('does not treat as no-op when indices differ in same container', () => {
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 2,
        containerItems: ['element-11', 'element-12', 'element-10'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      expect(resolveReorderParams(context)).not.toBeNull();
    });
  });

  describe('afterElementId resolution boundary', () => {
    it('returns afterElementID=null for overIndex exactly 0', () => {
      const context: ReorderContext = {
        activeId: 'row-5',
        overContainerParentId: 100,
        overIndex: 0,
        containerItems: ['row-5', 'row-3', 'row-7'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      expect(result).toEqual({
        elementID: 5,
        targetParentId: 100,
        afterElementID: null,
      });
    });

    it('skips the active item when walking backwards for afterElementId', () => {
      // containerItems: ['element-10', 'element-11', 'element-12']
      // Active is element-10, overIndex is 1
      // Walking backwards from index 0: element-10 is the active item, must skip
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 1,
        containerItems: ['element-10', 'element-11', 'element-12'],
        sourceContainerParentId: 200,
        sourceIndex: 0,
      };

      const result = resolveReorderParams(context);

      // element-10 is at index 0 which is the only item before overIndex 1
      // Since it's the active element, it must be skipped → afterElementID = null
      expect(result!.afterElementID).toBeNull();
    });

    it('finds non-active item when active is sandwiched', () => {
      // containerItems: ['element-11', 'element-10', 'element-12']
      // Active is element-10, overIndex is 2
      // Walking backwards: index 1 = element-10 (skip), index 0 = element-11 (use)
      const context: ReorderContext = {
        activeId: 'element-10',
        overContainerParentId: 200,
        overIndex: 2,
        containerItems: ['element-11', 'element-10', 'element-12'],
        sourceContainerParentId: 200,
        sourceIndex: 1,
      };

      const result = resolveReorderParams(context);

      expect(result!.afterElementID).toBe(11);
    });
  });
});
