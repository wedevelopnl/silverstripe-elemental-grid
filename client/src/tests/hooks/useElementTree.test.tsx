import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useElementTree } from '@/hooks/useElementTree';
import type { ElementTreeResponse } from '@/types/elements';

const mockFetchElementTree = vi.fn();

vi.mock('@/api/endpoints', () => ({
  fetchElementTree: (...args: unknown[]) => mockFetchElementTree(...args),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
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

describe('useElementTree', () => {
  afterEach(() => {
    mockFetchElementTree.mockReset();
  });

  it('fetches element tree for given pageId', async () => {
    const mockTree: ElementTreeResponse = {
      '42': [],
    };
    mockFetchElementTree.mockResolvedValue(mockTree);

    const { result } = renderHook(() => useElementTree(42, 'main'), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockFetchElementTree).toHaveBeenCalledWith(42, 'main');
    expect(result.current.data).toEqual(mockTree);
  });

  it('does not fetch when pageId is null', () => {
    mockFetchElementTree.mockResolvedValue({});

    const { result } = renderHook(() => useElementTree(null, 'main'), {
      wrapper: createWrapper(),
    });

    expect(result.current.fetchStatus).toBe('idle');
    expect(mockFetchElementTree).not.toHaveBeenCalled();
  });

  it('exposes error when fetch fails', async () => {
    mockFetchElementTree.mockRejectedValue(new Error('Network error'));

    const { result } = renderHook(() => useElementTree(1, 'main'), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeInstanceOf(Error);
  });
});
