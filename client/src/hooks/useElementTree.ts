import { useQuery } from '@tanstack/react-query';
import { fetchElementTree } from '@/api/endpoints';
import type { ElementTreeResponse } from '@/types/elements';
import type { ApiError } from '@/api/errors';
import { queryKeys } from './queryKeys';

/**
 * Fetches and caches the element tree for a CMS page.
 * Disabled when pageId is null (no page selected).
 */
export function useElementTree(pageId: number | null) {
  return useQuery<ElementTreeResponse, ApiError>({
    queryKey: pageId !== null
      ? queryKeys.elementTree.byPage(pageId)
      : ['elementTree', 'disabled'],
    queryFn: () => {
      if (pageId === null) {
        throw new Error('pageId is required — query should be disabled');
      }
      return fetchElementTree(pageId);
    },
    enabled: pageId !== null,
  });
}
