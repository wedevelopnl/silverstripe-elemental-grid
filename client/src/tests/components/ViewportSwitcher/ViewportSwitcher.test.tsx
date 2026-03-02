import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';

import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import { ViewportProvider } from '@/hooks/ViewportContext';

vi.mock('@/utils/gridAdapter', () => ({
  getViewports: vi.fn(() => [
    { key: 'xs', label: 'Extra Small', minWidth: null },
    { key: 'sm', label: 'Small', minWidth: 576 },
    { key: 'md', label: 'Medium', minWidth: 768 },
    { key: 'lg', label: 'Large', minWidth: 992 },
  ]),
  getDefaultViewport: vi.fn(() => 'md'),
}));

function createWrapper(initialViewport = 'xs') {
  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <ViewportProvider initialViewport={initialViewport}>
        {children}
      </ViewportProvider>
    );
  };
}

describe('ViewportSwitcher', () => {
  it('renders a button per viewport', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper() });

    const buttons = screen.getAllByRole('button');
    expect(buttons).toHaveLength(4);
  });

  it('renders viewport labels as button text', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper() });

    expect(screen.getByText('Extra Small')).toBeDefined();
    expect(screen.getByText('Small')).toBeDefined();
    expect(screen.getByText('Medium')).toBeDefined();
    expect(screen.getByText('Large')).toBeDefined();
  });

  it('marks active viewport button as aria-pressed="true"', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper('sm') });

    const activeButton = screen.getByText('Small');
    expect(activeButton.getAttribute('aria-pressed')).toBe('true');
  });

  it('marks inactive viewport buttons as aria-pressed="false"', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper('sm') });

    const inactiveLabels = ['Extra Small', 'Medium', 'Large'];
    for (const label of inactiveLabels) {
      const button = screen.getByText(label);
      expect(button.getAttribute('aria-pressed')).toBe('false');
    }
  });

  it('switches active viewport when a button is clicked', async () => {
    const user = userEvent.setup();

    render(<ViewportSwitcher />, { wrapper: createWrapper('xs') });

    await user.click(screen.getByText('Medium'));

    expect(screen.getByText('Medium').getAttribute('aria-pressed')).toBe('true');
    expect(screen.getByText('Extra Small').getAttribute('aria-pressed')).toBe('false');
  });

  it('applies active modifier class to the active viewport button', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper('sm') });

    const activeButton = screen.getByText('Small');
    expect(activeButton.classList.contains('viewport-switcher__button--active')).toBe(true);
  });

  it('does not apply active modifier class to inactive viewport buttons', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper('sm') });

    const inactiveLabels = ['Extra Small', 'Medium', 'Large'];
    for (const label of inactiveLabels) {
      const button = screen.getByText(label);
      expect(button.classList.contains('viewport-switcher__button--active')).toBe(false);
    }
  });

  it('marks active viewport button as aria-disabled', () => {
    render(<ViewportSwitcher />, { wrapper: createWrapper('sm') });

    const activeButton = screen.getByText('Small');
    expect(activeButton.getAttribute('aria-disabled')).toBe('true');

    const inactiveLabels = ['Extra Small', 'Medium', 'Large'];
    for (const label of inactiveLabels) {
      const button = screen.getByText(label);
      expect(button.hasAttribute('aria-disabled')).toBe(false);
    }
  });

  it('does not change state when clicking the already-active button', async () => {
    const user = userEvent.setup();

    render(<ViewportSwitcher />, { wrapper: createWrapper('sm') });

    await user.click(screen.getByText('Small'));

    // Still active after clicking
    expect(screen.getByText('Small').getAttribute('aria-pressed')).toBe('true');
  });
});
