import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import type { ViewportConfig } from '@/types/adapter';

const viewports: readonly ViewportConfig[] = [
  { key: 'xs', label: 'Extra Small', minWidth: null },
  { key: 'sm', label: 'Small', minWidth: 576 },
  { key: 'md', label: 'Medium', minWidth: 768 },
  { key: 'lg', label: 'Large', minWidth: 992 },
];

describe('ViewportSwitcher', () => {
  it('renders a button per viewport', () => {
    const onViewportChange = vi.fn();

    render(
      <ViewportSwitcher
        viewports={viewports}
        activeViewport="xs"
        onViewportChange={onViewportChange}
      />,
    );

    const buttons = screen.getAllByRole('button');
    expect(buttons).toHaveLength(viewports.length);
  });

  it('renders viewport labels as button text', () => {
    const onViewportChange = vi.fn();

    render(
      <ViewportSwitcher
        viewports={viewports}
        activeViewport="xs"
        onViewportChange={onViewportChange}
      />,
    );

    for (const viewport of viewports) {
      expect(screen.getByText(viewport.label)).toBeDefined();
    }
  });

  it('marks active viewport button as aria-pressed="true"', () => {
    const onViewportChange = vi.fn();

    render(
      <ViewportSwitcher
        viewports={viewports}
        activeViewport="sm"
        onViewportChange={onViewportChange}
      />,
    );

    const activeButton = screen.getByText('Small');
    expect(activeButton.getAttribute('aria-pressed')).toBe('true');
  });

  it('marks inactive viewport buttons as aria-pressed="false"', () => {
    const onViewportChange = vi.fn();

    render(
      <ViewportSwitcher
        viewports={viewports}
        activeViewport="sm"
        onViewportChange={onViewportChange}
      />,
    );

    const inactiveLabels = ['Extra Small', 'Medium', 'Large'];
    for (const label of inactiveLabels) {
      const button = screen.getByText(label);
      expect(button.getAttribute('aria-pressed')).toBe('false');
    }
  });

  it('calls onViewportChange with the viewport key on click', async () => {
    const user = userEvent.setup();
    const onViewportChange = vi.fn();

    render(
      <ViewportSwitcher
        viewports={viewports}
        activeViewport="xs"
        onViewportChange={onViewportChange}
      />,
    );

    await user.click(screen.getByText('Medium'));

    expect(onViewportChange).toHaveBeenCalledTimes(1);
    expect(onViewportChange).toHaveBeenCalledWith('md');
  });

  it('does NOT call onViewportChange when clicking the already-active tab', async () => {
    const user = userEvent.setup();
    const onViewportChange = vi.fn();

    render(
      <ViewportSwitcher
        viewports={viewports}
        activeViewport="sm"
        onViewportChange={onViewportChange}
      />,
    );

    await user.click(screen.getByText('Small'));

    expect(onViewportChange).not.toHaveBeenCalled();
  });
});
