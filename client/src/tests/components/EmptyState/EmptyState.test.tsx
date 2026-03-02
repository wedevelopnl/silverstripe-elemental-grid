import { render, screen } from '@testing-library/react';

import EmptyState from '@/components/EmptyState/EmptyState';

describe('EmptyState', () => {
  it('renders message text', () => {
    render(<EmptyState message="No content blocks" />);

    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('applies .empty-state root class', () => {
    const { container } = render(<EmptyState message="No content blocks" />);

    const root = container.querySelector('.empty-state');
    expect(root).not.toBeNull();
  });

  it('applies .empty-state--centered modifier when variant="centered"', () => {
    const { container } = render(
      <EmptyState message="No sections yet" variant="centered" />,
    );

    const root = container.querySelector('.empty-state');
    expect(root?.classList.contains('empty-state--centered')).toBe(true);
  });

  it('does not apply centered modifier when variant is undefined', () => {
    const { container } = render(<EmptyState message="No content blocks" />);

    const root = container.querySelector('.empty-state');
    expect(root?.classList.contains('empty-state--centered')).toBe(false);
  });
});
