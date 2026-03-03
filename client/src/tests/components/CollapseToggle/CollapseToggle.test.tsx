import { render, screen, fireEvent } from '@testing-library/react';

import CollapseToggle from '@/components/CollapseToggle/CollapseToggle';

describe('CollapseToggle', () => {
  it('renders a button with aria-expanded=true when not collapsed', () => {
    render(<CollapseToggle isCollapsed={false} onToggle={vi.fn()} label="Section" />);

    const button = screen.getByRole('button');
    expect(button.getAttribute('aria-expanded')).toBe('true');
  });

  it('renders a button with aria-expanded=false when collapsed', () => {
    render(<CollapseToggle isCollapsed={true} onToggle={vi.fn()} label="Section" />);

    const button = screen.getByRole('button');
    expect(button.getAttribute('aria-expanded')).toBe('false');
  });

  it('sets aria-label to "Collapse {label}" when expanded', () => {
    render(<CollapseToggle isCollapsed={false} onToggle={vi.fn()} label="My Section" />);

    const button = screen.getByRole('button');
    expect(button.getAttribute('aria-label')).toBe('Collapse My Section');
  });

  it('sets aria-label to "Expand {label}" when collapsed', () => {
    render(<CollapseToggle isCollapsed={true} onToggle={vi.fn()} label="My Section" />);

    const button = screen.getByRole('button');
    expect(button.getAttribute('aria-label')).toBe('Expand My Section');
  });

  it('calls onToggle when clicked', () => {
    const onToggle = vi.fn();
    render(<CollapseToggle isCollapsed={false} onToggle={onToggle} label="Section" />);

    fireEvent.click(screen.getByRole('button'));

    expect(onToggle).toHaveBeenCalledOnce();
  });

  it('applies --collapsed modifier class when collapsed', () => {
    const { container } = render(
      <CollapseToggle isCollapsed={true} onToggle={vi.fn()} label="Section" />,
    );

    const button = container.querySelector('.collapse-toggle');
    expect(button?.classList.contains('collapse-toggle--collapsed')).toBe(true);
  });

  it('does not apply --collapsed modifier class when expanded', () => {
    const { container } = render(
      <CollapseToggle isCollapsed={false} onToggle={vi.fn()} label="Section" />,
    );

    const button = container.querySelector('.collapse-toggle');
    expect(button?.classList.contains('collapse-toggle--collapsed')).toBe(false);
  });

  it('stops event propagation on click', () => {
    const outerClick = vi.fn();
    const { container } = render(
      // eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
      <div onClick={outerClick}>
        <CollapseToggle isCollapsed={false} onToggle={vi.fn()} label="Section" />
      </div>,
    );

    fireEvent.click(container.querySelector('.collapse-toggle')!);

    expect(outerClick).not.toHaveBeenCalled();
  });

  it('renders with data-testid="collapse-toggle"', () => {
    render(<CollapseToggle isCollapsed={false} onToggle={vi.fn()} label="Section" />);

    expect(screen.getByTestId('collapse-toggle')).toBeDefined();
  });
});
