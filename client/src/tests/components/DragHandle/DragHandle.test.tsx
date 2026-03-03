import { render, screen, fireEvent } from '@testing-library/react';

import DragHandle from '@/components/DragHandle/DragHandle';

describe('DragHandle', () => {
  const defaultListeners = {
    onPointerDown: vi.fn(),
    onKeyDown: vi.fn(),
  };

  const defaultAttributes = {
    role: 'button',
    tabIndex: 0,
    'aria-disabled': false,
    'aria-pressed': undefined,
    'aria-roledescription': 'sortable',
    'aria-describedby': 'DndDescribedBy-0',
  } as const;

  it('renders a button with data-testid="drag-handle"', () => {
    render(
      <DragHandle listeners={defaultListeners} attributes={defaultAttributes} />,
    );

    expect(screen.getByTestId('drag-handle')).toBeDefined();
  });

  it('renders a button with type="button"', () => {
    render(
      <DragHandle listeners={defaultListeners} attributes={defaultAttributes} />,
    );

    const button = screen.getByTestId('drag-handle');
    expect(button.getAttribute('type')).toBe('button');
  });

  it('has default aria-label "Drag to reorder"', () => {
    render(
      <DragHandle listeners={defaultListeners} attributes={defaultAttributes} />,
    );

    const button = screen.getByTestId('drag-handle');
    expect(button.getAttribute('aria-label')).toBe('Drag to reorder');
  });

  it('allows overriding the aria-label via label prop', () => {
    render(
      <DragHandle
        listeners={defaultListeners}
        attributes={defaultAttributes}
        label="Drag section"
      />,
    );

    const button = screen.getByTestId('drag-handle');
    expect(button.getAttribute('aria-label')).toBe('Drag section');
  });

  it('spreads listeners onto the button', () => {
    const onPointerDown = vi.fn();
    const listeners = { onPointerDown };

    render(
      <DragHandle listeners={listeners} attributes={defaultAttributes} />,
    );

    const button = screen.getByTestId('drag-handle');
    fireEvent.pointerDown(button);

    expect(onPointerDown).toHaveBeenCalledOnce();
  });

  it('spreads attributes onto the button', () => {
    render(
      <DragHandle listeners={defaultListeners} attributes={defaultAttributes} />,
    );

    const button = screen.getByTestId('drag-handle');
    expect(button.getAttribute('aria-roledescription')).toBe('sortable');
    expect(button.getAttribute('aria-describedby')).toBe('DndDescribedBy-0');
  });

  it('renders the grip icon span', () => {
    const { container } = render(
      <DragHandle listeners={defaultListeners} attributes={defaultAttributes} />,
    );

    const icon = container.querySelector('.drag-handle__icon');
    expect(icon).not.toBeNull();
    expect(icon?.getAttribute('aria-hidden')).toBe('true');
  });

  it('handles undefined listeners gracefully', () => {
    render(
      <DragHandle listeners={undefined} attributes={defaultAttributes} />,
    );

    expect(screen.getByTestId('drag-handle')).toBeDefined();
  });
});
