import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useState, type ReactNode } from 'react';

interface GridQueryProviderProps {
  readonly children: ReactNode;
}

/**
 * Provides a per-mount QueryClient to the grid editor component tree.
 *
 * Each entwine-mounted GridEditor gets its own cache — no stale data
 * leaks between CMS page navigations and clean unmount disposal.
 */
export default function GridQueryProvider({
  children,
}: GridQueryProviderProps) {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 30_000,
            gcTime: 5 * 60_000,
            refetchOnWindowFocus: false,
            retry: false,
          },
        },
      }),
  );

  return (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
}
