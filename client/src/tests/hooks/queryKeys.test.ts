import { queryKeys } from '@/hooks/queryKeys';

describe('queryKeys', () => {
  describe('elementTree', () => {
    it('all() returns base key', () => {
      expect(queryKeys.elementTree.all()).toEqual(['elementTree']);
    });

    it('byPage() returns key scoped to page ID and zone', () => {
      expect(queryKeys.elementTree.byPage(42, 'main')).toEqual([
        'elementTree',
        42,
        'main',
      ]);
    });

    it('different page IDs produce different keys', () => {
      expect(queryKeys.elementTree.byPage(1, 'main')).not.toEqual(
        queryKeys.elementTree.byPage(2, 'main'),
      );
    });

    it('different zones produce different keys', () => {
      expect(queryKeys.elementTree.byPage(1, 'body')).not.toEqual(
        queryKeys.elementTree.byPage(1, 'sidebar'),
      );
    });
  });
});
