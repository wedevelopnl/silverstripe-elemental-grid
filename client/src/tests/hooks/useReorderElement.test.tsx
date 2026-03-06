import { renderHook, act, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useReorderElement } from '@/hooks/useElementMutations';
import { queryKeys } from '@/hooks/queryKeys';
import { ApiError } from '@/api/errors';
import type {
  SimpleElementNode,
  ColumnNode,
  ElementTreeResponse,
} from '@/types/elements';
import type { ReorderElementParams } from '@/api/endpoints';

// --- Test factories ---

function makeElement(id: number, parentId: number): SimpleElementNode {
  return {
    id,
    parentId,
    title: `Element ${id}`,
    blockSchema: {
      typeName: 'Element',
      label: 'Element',
      type: 'Element',
      title: '',
      summary: '',
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
  parentId: number,
): ColumnNode {
  return {
    id,
    parentId,
    title: `Column ${id}`,
    blockSchema: {
      typeName: 'Column',
      label: 'Column',
      type: 'Column',
      title: '',
      summary: '',
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
    gridSettings: { md: { width: 6, offset: 0, visible: true } },
  };
}

// --- Mocks ---

const mockReorderElement = vi.fn();
const mockShowToast = vi.fn();

vi.mock('@/api/endpoints', () => ({
  createElement: vi.fn(),
  publishElement: vi.fn(),
  unpublishElement: vi.fn(),
  deleteElement: vi.fn(),
  duplicateElement: vi.fn(),
  reorderElement: (...args: unknown[]) => mockReorderElement(...args),
}));

vi.mock('@/utils/toast', () => ({
  showToast: (...args: unknown[]) => mockShowToast(...args),
}));

let queryClient: QueryClient;

function createWrapper() {
  queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        {children}
      </QueryClientProvider>
    );
  };
}

const PAGE_ID = 42;

describe('useReorderElement', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    mockReorderElement.mockReset();
    mockShowToast.mockReset();
  });

  it('calls reorderElement endpoint with the provided params', async () => {
    mockReorderElement.mockResolvedValue(undefined);
    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), {
      wrapper: createWrapper(),
    });

    const params: ReorderElementParams = {
      elementID: 10,
      targetParentId: 1,
      afterElementID: null,
    };

    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    await act(() => result.current.mutateAsync({ params, tree }));

    expect(mockReorderElement).toHaveBeenCalledWith(params);
  });

  it('applies optimistic update to the cache on mutate', async () => {
    // Never resolve to keep the mutation pending so we can inspect the cache
    mockReorderElement.mockReturnValue(new Promise(() => {}));

    const wrapper = createWrapper();
    const tree: ElementTreeResponse = {
      '100': [
        makeColumn(1, [makeElement(10, 1), makeElement(11, 1)], 100),
      ],
    };

    // Seed the query cache with initial tree data
    queryClient.setQueryData(queryKeys.elementTree.byPage(PAGE_ID, 'main'), tree);

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    const params: ReorderElementParams = {
      elementID: 11,
      targetParentId: 1,
      afterElementID: null,
    };

    // Start the mutation (don't await — it never resolves)
    act(() => {
      result.current.mutate({ params, tree });
    });

    await waitFor(() => {
      const cached = queryClient.getQueryData<ElementTreeResponse>(
        queryKeys.elementTree.byPage(PAGE_ID, 'main'),
      );
      expect(cached).toBeDefined();

      const children = (cached!['100'][0] as ColumnNode).children!;
      // Element 11 should now be at the start
      expect(children.map((c) => c.id)).toEqual([11, 10]);
    });
  });

  it('rolls back the cache on error', async () => {
    const error = new Error('Server error');
    mockReorderElement.mockRejectedValue(error);

    const wrapper = createWrapper();
    const tree: ElementTreeResponse = {
      '100': [
        makeColumn(1, [makeElement(10, 1), makeElement(11, 1)], 100),
      ],
    };

    // Seed the cache
    queryClient.setQueryData(queryKeys.elementTree.byPage(PAGE_ID, 'main'), tree);

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    const params: ReorderElementParams = {
      elementID: 11,
      targetParentId: 1,
      afterElementID: null,
    };

    await act(async () => {
      try {
        await result.current.mutateAsync({ params, tree });
      } catch {
        // Expected — error captured by TanStack Query
      }
    });

    // Cache should be restored to original order
    await waitFor(() => {
      const cached = queryClient.getQueryData<ElementTreeResponse>(
        queryKeys.elementTree.byPage(PAGE_ID, 'main'),
      );
      expect(cached).toBeDefined();

      const children = (cached!['100'][0] as ColumnNode).children!;
      expect(children.map((c) => c.id)).toEqual([10, 11]);
    });
  });

  it('invalidates the tree query on settled (success)', async () => {
    mockReorderElement.mockResolvedValue(undefined);
    const wrapper = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    queryClient.setQueryData(queryKeys.elementTree.byPage(PAGE_ID, 'main'), tree);

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    await act(() =>
      result.current.mutateAsync({
        params: { elementID: 10, targetParentId: 1, afterElementID: null },
        tree,
      }),
    );

    await waitFor(() =>
      expect(invalidateSpy).toHaveBeenCalledWith({
        queryKey: queryKeys.elementTree.byPage(PAGE_ID, 'main'),
      }),
    );
  });

  it('invalidates the tree query on settled (error)', async () => {
    mockReorderElement.mockRejectedValue(new Error('fail'));
    const wrapper = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    queryClient.setQueryData(queryKeys.elementTree.byPage(PAGE_ID, 'main'), tree);

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    await act(async () => {
      try {
        await result.current.mutateAsync({
          params: { elementID: 10, targetParentId: 1, afterElementID: null },
          tree,
        });
      } catch {
        // Expected
      }
    });

    await waitFor(() =>
      expect(invalidateSpy).toHaveBeenCalledWith({
        queryKey: queryKeys.elementTree.byPage(PAGE_ID, 'main'),
      }),
    );
  });

  it('exposes error state when mutation fails', async () => {
    const error = new Error('Server error');
    mockReorderElement.mockRejectedValue(error);

    const wrapper = createWrapper();
    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    await act(async () => {
      try {
        await result.current.mutateAsync({
          params: { elementID: 10, targetParentId: 1, afterElementID: null },
          tree,
        });
      } catch {
        // Expected
      }
    });

    await waitFor(() => expect(result.current.error).toBe(error));
  });

  it('shows error toast when mutation fails', async () => {
    const error = new Error('Reorder failed');
    mockReorderElement.mockRejectedValue(error);

    const wrapper = createWrapper();
    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    await act(async () => {
      try {
        await result.current.mutateAsync({
          params: { elementID: 10, targetParentId: 1, afterElementID: null },
          tree,
        });
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(mockShowToast).toHaveBeenCalledWith('Reorder failed');
    });
  });

  it('shows ApiError message in toast for 422 responses', async () => {
    const error = new ApiError(422, 'Unprocessable Entity');
    mockReorderElement.mockRejectedValue(error);

    const wrapper = createWrapper();
    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    await act(async () => {
      try {
        await result.current.mutateAsync({
          params: { elementID: 10, targetParentId: 1, afterElementID: null },
          tree,
        });
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(mockShowToast).toHaveBeenCalledWith('API error 422: Unprocessable Entity');
    });
  });

  it('exposes ApiError with status on mutation failure', async () => {
    const error = new ApiError(422, 'Unprocessable Entity');
    mockReorderElement.mockRejectedValue(error);

    const wrapper = createWrapper();
    const tree: ElementTreeResponse = {
      '100': [makeColumn(1, [makeElement(10, 1)], 100)],
    };

    const { result } = renderHook(() => useReorderElement(PAGE_ID, 'main'), { wrapper });

    await act(async () => {
      try {
        await result.current.mutateAsync({
          params: { elementID: 10, targetParentId: 1, afterElementID: null },
          tree,
        });
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.error).toBeInstanceOf(ApiError);
      expect((result.current.error as ApiError).status).toBe(422);
    });
  });
});
