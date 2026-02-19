import { queryKeys } from '@/hooks/queryKeys';

describe('queryKeys', () => {
  describe('elementTree', () => {
    it('all() returns base key', () => {
      expect(queryKeys.elementTree.all()).toEqual(['elementTree']);
    });

    it('byPage() returns key scoped to page ID', () => {
      expect(queryKeys.elementTree.byPage(42)).toEqual([
        'elementTree',
        42,
      ]);
    });

    it('different page IDs produce different keys', () => {
      expect(queryKeys.elementTree.byPage(1)).not.toEqual(
        queryKeys.elementTree.byPage(2),
      );
    });
  });
});
