import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import type { EnrichedColumnNode } from '@/types/enriched';
import { createViewportWrapper } from '@/tests/helpers/viewportTestUtils';
import { getWidthClass, getOffsetClass, getColumnCount } from '@/utils/gridAdapter';

vi.mock('@/utils/gridAdapter', () => ({
  getColumnCount: vi.fn(() => 12),
  getWidthClass: vi.fn((width: number) => `col-${width}`),
  getOffsetClass: vi.fn((offset: number) => `offset-${offset}`),
  getDefaultViewport: vi.fn(() => 'md'),
}));

function makeColumn(overrides: Partial<EnrichedColumnNode> = {}): EnrichedColumnNode {
  return {
    id: 10,
    title: 'Column',
    blockSchema: {
      typeName: 'WeDevelop\\ElementalGrid\\Column',
      label: 'Column',
      actions: { edit: '/admin/elemental/edit/10' },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'column',
    allowedTypes: null,
    children: null,
    childAreaId: 100,
    gridSettings: {
      md: { width: 6, offset: 0, visible: true },
    },
    isCollapsed: false,
    toggle: vi.fn(),
    ...overrides,
  };
}

describe('ColumnBlock', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getColumnCount).mockReturnValue(12);
    vi.mocked(getWidthClass).mockImplementation((width: number) => `col-${width}`);
    vi.mocked(getOffsetClass).mockImplementation((offset: number) => `offset-${offset}`);
  });

  it('renders fraction badge for the active viewport', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('6/12')).toBeDefined();
  });

  it('applies width class from getWidthClass()', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('col-6')).toBe(true);
  });

  it('applies offset class from getOffsetClass() when offset > 0', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 4, offset: 2, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('offset-2')).toBe(true);
  });

  it('does not apply offset class when offset is 0', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('offset-0')).toBe(false);
  });

  it('renders child elements as ElementCards', () => {
    const column = makeColumn({
      children: [
        {
          id: 100,
          title: 'Hero Banner',
          blockSchema: {
            typeName: 'Content',
            label: 'Content',
            actions: { edit: '/edit/100' },
            content: 'Hero content',
          },
          obsoleteClassName: null,
          version: 1,
          isPublished: true,
          isLiveVersion: true,
          canDelete: true,
          canPublish: true,
          canUnpublish: false,
          canCreate: true,
          statusFlags: {},
        },
        {
          id: 101,
          title: 'Text Block',
          blockSchema: {
            typeName: 'Content',
            label: 'Content',
            actions: { edit: '/edit/101' },
            content: 'Text content',
          },
          obsoleteClassName: null,
          version: 1,
          canDelete: true,
          canPublish: true,
          canUnpublish: false,
          canCreate: true,
          statusFlags: {},
        },
      ],
    });

    render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('Hero Banner')).toBeDefined();
    expect(screen.getByText('Text Block')).toBeDefined();
  });

  it('renders EmptyState when children is null', () => {
    const column = makeColumn({ children: null });

    render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('renders EmptyState when children is empty array', () => {
    const column = makeColumn({ children: [] });

    render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('applies hidden modifier when visible is false', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: false } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--hidden')).toBe(true);
  });

  it('does not apply hidden modifier when visible is true', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--hidden')).toBe(false);
  });

  it('shows "hidden" instead of fraction badge when not visible', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: false } },
    });

    render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('hidden')).toBeDefined();
    expect(screen.queryByText('6/12')).toBeNull();
  });

  it('applies publication state modifier class for draft column', () => {
    const column = makeColumn({
      statusFlags: { addedtodraft: { text: 'Draft', title: 'Item has not been published yet' } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--draft')).toBe(true);
  });

  it('applies publication state modifier class for published column', () => {
    const column = makeColumn({
      statusFlags: {},
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--published')).toBe(true);
  });

  it('applies publication state modifier class for modified column', () => {
    const column = makeColumn({
      statusFlags: { modified: { text: 'Modified', title: 'Item has unpublished changes' } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--modified')).toBe(true);
  });

  it('falls back to full width when viewport key is missing from gridSettings', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper('lg') },
    );

    // Falls back to full width (columnCount = 12)
    expect(screen.getByText('12/12')).toBeDefined();
    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('col-12')).toBe(true);
  });

  it('does not apply offset class when falling back (offset defaults to 0)', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 3, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper('lg') },
    );

    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('offset-0')).toBe(false);
    expect(outerDiv?.classList.contains('offset-3')).toBe(false);
  });

  it('falls back to visible when viewport key is missing', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: false } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper('lg') },
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--hidden')).toBe(false);
    expect(screen.getByText('12/12')).toBeDefined();
  });

  it('calls getWidthClass with the resolved column width', () => {
    vi.mocked(getWidthClass).mockReturnValue('custom-col-8');
    const column = makeColumn({
      gridSettings: { md: { width: 8, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(getWidthClass).toHaveBeenCalledWith(8);
    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('custom-col-8')).toBe(true);
  });

  it('calls getOffsetClass with the resolved column offset', () => {
    vi.mocked(getOffsetClass).mockReturnValue('custom-offset-3');
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 3, visible: true } },
    });

    const { container } = render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(getOffsetClass).toHaveBeenCalledWith(3);
    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('custom-offset-3')).toBe(true);
  });

  it('does not call getOffsetClass when offset is 0', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    render(
      <ColumnBlock column={column} />,
      { wrapper: createViewportWrapper() },
    );

    expect(getOffsetClass).not.toHaveBeenCalled();
  });

  describe('collapse', () => {
    it('renders a collapse toggle button', () => {
      const column = makeColumn();

      render(
        <ColumnBlock column={column} />,
        { wrapper: createViewportWrapper() },
      );

      expect(screen.getByTestId('collapse-toggle')).toBeDefined();
    });

    it('wires toggle to CollapseToggle onToggle', async () => {
      const toggle = vi.fn();
      const column = makeColumn({ id: 55, toggle });
      const user = userEvent.setup();

      render(
        <ColumnBlock column={column} />,
        { wrapper: createViewportWrapper() },
      );

      await user.click(screen.getByTestId('collapse-toggle'));
      expect(toggle).toHaveBeenCalledOnce();
    });

    it('applies --collapsed modifier when collapsed', () => {
      const column = makeColumn({ isCollapsed: true });

      const { container } = render(
        <ColumnBlock column={column} />,
        { wrapper: createViewportWrapper() },
      );

      const inner = container.querySelector('.column-block');
      expect(inner?.classList.contains('column-block--collapsed')).toBe(true);
    });

    it('does not apply --collapsed modifier when expanded', () => {
      const column = makeColumn({ isCollapsed: false });

      const { container } = render(
        <ColumnBlock column={column} />,
        { wrapper: createViewportWrapper() },
      );

      const inner = container.querySelector('.column-block');
      expect(inner?.classList.contains('column-block--collapsed')).toBe(false);
    });
  });
});
