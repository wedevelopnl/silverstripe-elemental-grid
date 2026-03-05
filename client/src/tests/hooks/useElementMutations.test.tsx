import { renderHook, act, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import {
  useCreateElement,
  usePublishElement,
  useUnpublishElement,
  useDeleteElement,
  useDuplicateElement,
} from '@/hooks/useElementMutations';
import { queryKeys } from '@/hooks/queryKeys';

const mockCreateElement = vi.fn();
const mockPublishElement = vi.fn();
const mockUnpublishElement = vi.fn();
const mockDeleteElement = vi.fn();
const mockDuplicateElement = vi.fn();

vi.mock('@/api/endpoints', () => ({
  createElement: (...args: unknown[]) => mockCreateElement(...args),
  publishElement: (...args: unknown[]) => mockPublishElement(...args),
  unpublishElement: (...args: unknown[]) => mockUnpublishElement(...args),
  deleteElement: (...args: unknown[]) => mockDeleteElement(...args),
  duplicateElement: (...args: unknown[]) => mockDuplicateElement(...args),
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

describe('useCreateElement', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    mockCreateElement.mockReset();
  });

  it('calls createElement endpoint with params as first argument', async () => {
    mockCreateElement.mockResolvedValue(undefined);
    const { result } = renderHook(() => useCreateElement(42), {
      wrapper: createWrapper(),
    });

    await act(() =>
      result.current.mutateAsync({
        elementClass: 'App\\MyElement',
        parentId: 10,
        parentClass: 'App\\MyContainer',
      }),
    );

    // TanStack Query v5 passes (variables, { client, meta, mutationKey })
    expect(mockCreateElement).toHaveBeenCalledWith(
      { elementClass: 'App\\MyElement', parentId: 10, parentClass: 'App\\MyContainer' },
      expect.anything(),
    );
  });

  it('invalidates element tree cache on success', async () => {
    mockCreateElement.mockResolvedValue(undefined);
    const wrapper = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useCreateElement(42), { wrapper });

    await act(() =>
      result.current.mutateAsync({
        elementClass: 'App\\MyElement',
        parentId: 10,
        parentClass: 'App\\MyContainer',
      }),
    );

    await waitFor(() =>
      expect(invalidateSpy).toHaveBeenCalledWith({
        queryKey: queryKeys.elementTree.byPage(42),
      }),
    );
  });
});

describe('usePublishElement', () => {
  afterEach(() => {
    mockPublishElement.mockReset();
  });

  it('calls publishElement endpoint', async () => {
    mockPublishElement.mockResolvedValue(undefined);
    const { result } = renderHook(() => usePublishElement(42), {
      wrapper: createWrapper(),
    });

    await act(() => result.current.mutateAsync(7));

    expect(mockPublishElement).toHaveBeenCalledWith(7, expect.anything());
  });
});

describe('useUnpublishElement', () => {
  afterEach(() => {
    mockUnpublishElement.mockReset();
  });

  it('calls unpublishElement endpoint', async () => {
    mockUnpublishElement.mockResolvedValue(undefined);
    const { result } = renderHook(() => useUnpublishElement(42), {
      wrapper: createWrapper(),
    });

    await act(() => result.current.mutateAsync(7));

    expect(mockUnpublishElement).toHaveBeenCalledWith(7, expect.anything());
  });
});

describe('useDeleteElement', () => {
  afterEach(() => {
    mockDeleteElement.mockReset();
  });

  it('calls deleteElement endpoint', async () => {
    mockDeleteElement.mockResolvedValue(undefined);
    const { result } = renderHook(() => useDeleteElement(42), {
      wrapper: createWrapper(),
    });

    await act(() => result.current.mutateAsync(3));

    expect(mockDeleteElement).toHaveBeenCalledWith(3, expect.anything());
  });
});

describe('useDuplicateElement', () => {
  afterEach(() => {
    mockDuplicateElement.mockReset();
  });

  it('calls duplicateElement endpoint and invalidates cache', async () => {
    mockDuplicateElement.mockResolvedValue(undefined);
    const wrapper = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useDuplicateElement(42), { wrapper });

    await act(() => result.current.mutateAsync(9));

    expect(mockDuplicateElement).toHaveBeenCalledWith(9, expect.anything());
    await waitFor(() =>
      expect(invalidateSpy).toHaveBeenCalledWith({
        queryKey: queryKeys.elementTree.byPage(42),
      }),
    );
  });

  it('exposes error when mutation fails', async () => {
    const error = new Error('Server error');
    mockDuplicateElement.mockRejectedValue(error);
    const { result } = renderHook(() => useDuplicateElement(42), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync(9);
      } catch {
        // Expected — error captured by TanStack Query
      }
    });

    await waitFor(() => expect(result.current.error).toBe(error));
  });
});
