import { render, screen } from '@testing-library/react';
import { useQueryClient } from '@tanstack/react-query';
import GridQueryProvider from '@/hooks/QueryProvider';

function QueryClientConsumer() {
  const client = useQueryClient();
  return <div data-testid="has-client">{client ? 'yes' : 'no'}</div>;
}

function QueryConfigInspector() {
  const client = useQueryClient();
  const defaults = client.getDefaultOptions();
  const queries = defaults.queries ?? {};

  return (
    <div
      data-testid="query-config"
      data-stale-time={String(queries.staleTime)}
      data-gc-time={String(queries.gcTime)}
      data-refetch-on-window-focus={String(queries.refetchOnWindowFocus)}
      data-retry={String(queries.retry)}
    />
  );
}

describe('GridQueryProvider', () => {
  it('provides a QueryClient to children', () => {
    render(
      <GridQueryProvider>
        <QueryClientConsumer />
      </GridQueryProvider>,
    );

    expect(screen.getByTestId('has-client').textContent).toBe('yes');
  });

  it('renders children', () => {
    render(
      <GridQueryProvider>
        <span>child content</span>
      </GridQueryProvider>,
    );

    expect(screen.getByText('child content')).toBeDefined();
  });

  it('configures QueryClient with CMS-appropriate defaults', () => {
    render(
      <GridQueryProvider>
        <QueryConfigInspector />
      </GridQueryProvider>,
    );

    const el = screen.getByTestId('query-config');
    expect(el.dataset.staleTime).toBe('30000');
    expect(el.dataset.gcTime).toBe('300000');
    expect(el.dataset.refetchOnWindowFocus).toBe('false');
    expect(el.dataset.retry).toBe('false');
  });
});
