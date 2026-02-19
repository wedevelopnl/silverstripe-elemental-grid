import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  createElement,
  deleteElement,
  duplicateElement,
  publishElement,
  unpublishElement,
} from '@/api/endpoints';
import type { CreateElementParams } from '@/api/endpoints';
import type { ApiError } from '@/api/errors';
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
