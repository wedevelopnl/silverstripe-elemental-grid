import { render, screen } from '@testing-library/react';
import { useQueryClient } from '@tanstack/react-query';
import GridQueryProvider from '@/hooks/QueryProvider';

function QueryClientConsumer() {
  const client = useQueryClient();
  return <div data-testid="has-client">{client ? 'yes' : 'no'}</div>;
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
});
