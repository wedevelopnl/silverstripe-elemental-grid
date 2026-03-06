import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  createElement,
  deleteElement,
  duplicateElement,
  publishElement,
  reorderElement,
  unpublishElement,
} from '@/api/endpoints';
import type { CreateElementParams, ReorderElementParams } from '@/api/endpoints';
import type { ApiError } from '@/api/errors';
import type { ElementTreeResponse } from '@/types/elements';
import { applyReorder } from '@/utils/applyReorder';
import { showToast } from '@/utils/toast';
import { queryKeys } from './queryKeys';

function useInvalidateOnSuccess(pageId: number, zone: string) {
  const queryClient = useQueryClient();

  return {
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: queryKeys.elementTree.byPage(pageId, zone),
      });
    },
  };
}

export function useCreateElement(pageId: number, zone: string) {
  return useMutation<void, ApiError, CreateElementParams>({
    mutationFn: createElement,
    ...useInvalidateOnSuccess(pageId, zone),
  });
}

export function usePublishElement(pageId: number, zone: string) {
  return useMutation<void, ApiError, number>({
    mutationFn: publishElement,
    ...useInvalidateOnSuccess(pageId, zone),
  });
}

export function useUnpublishElement(pageId: number, zone: string) {
  return useMutation<void, ApiError, number>({
    mutationFn: unpublishElement,
    ...useInvalidateOnSuccess(pageId, zone),
  });
}

export function useDeleteElement(pageId: number, zone: string) {
  return useMutation<void, ApiError, number>({
    mutationFn: deleteElement,
    ...useInvalidateOnSuccess(pageId, zone),
  });
}

export function useDuplicateElement(pageId: number, zone: string) {
  return useMutation<void, ApiError, number>({
    mutationFn: duplicateElement,
    ...useInvalidateOnSuccess(pageId, zone),
  });
}

interface ReorderMutationVariables {
  params: ReorderElementParams;
  tree: ElementTreeResponse;
}

export function useReorderElement(pageId: number, zone: string) {
  const queryClient = useQueryClient();
  const queryKey = queryKeys.elementTree.byPage(pageId, zone);

  return useMutation<void, ApiError, ReorderMutationVariables, ElementTreeResponse | undefined>({
    mutationFn: ({ params }) => reorderElement(params),
    onMutate: async ({ params, tree }) => {
      // Cancel in-flight queries to avoid overwriting the optimistic update
      await queryClient.cancelQueries({ queryKey });

      // Snapshot the current cache for rollback
      const snapshot = queryClient.getQueryData<ElementTreeResponse>(queryKey);

      // Apply the optimistic reorder to the tree
      const optimistic = applyReorder(
        tree,
        params.elementID,
        params.targetParentId,
        params.afterElementID,
      );

      queryClient.setQueryData(queryKey, optimistic);

      return snapshot;
    },
    onError: (error, _variables, snapshot) => {
      if (snapshot !== undefined) {
        queryClient.setQueryData(queryKey, snapshot);
      }
      showToast(error.message);
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey });
    },
  });
}
