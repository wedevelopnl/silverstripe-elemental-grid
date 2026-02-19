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
    queryKey: queryKeys.elementTree.byPage(pageId!),
    queryFn: () => fetchElementTree(pageId!),
    enabled: pageId !== null,
  });
}
