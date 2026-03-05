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

function useInvalidateOnSuccess(pageId: number) {
  const queryClient = useQueryClient();

  return {
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: queryKeys.elementTree.byPage(pageId),
      });
    },
  };
}

export function useCreateElement(pageId: number) {
  return useMutation<void, ApiError, CreateElementParams>({
    mutationFn: createElement,
    ...useInvalidateOnSuccess(pageId),
  });
}

export function usePublishElement(pageId: number) {
  return useMutation<void, ApiError, number>({
    mutationFn: publishElement,
    ...useInvalidateOnSuccess(pageId),
  });
}

export function useUnpublishElement(pageId: number) {
  return useMutation<void, ApiError, number>({
    mutationFn: unpublishElement,
    ...useInvalidateOnSuccess(pageId),
  });
}

export function useDeleteElement(pageId: number) {
  return useMutation<void, ApiError, number>({
    mutationFn: deleteElement,
    ...useInvalidateOnSuccess(pageId),
  });
}

export function useDuplicateElement(pageId: number) {
  return useMutation<void, ApiError, number>({
    mutationFn: duplicateElement,
    ...useInvalidateOnSuccess(pageId),
  });
}

interface ReorderMutationVariables {
  params: ReorderElementParams;
  tree: ElementTreeResponse;
}

export function useReorderElement(pageId: number) {
  const queryClient = useQueryClient();
  const queryKey = queryKeys.elementTree.byPage(pageId);

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
