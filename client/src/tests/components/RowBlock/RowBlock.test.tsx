import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import RowBlock from '@/components/RowBlock/RowBlock';
import type { EnrichedRowNode, EnrichedColumnNode } from '@/types/enriched';
import { createViewportWrapper } from '@/tests/helpers/viewportTestUtils';
import {
  getRowClasses,
  getColumnCount,
  getWidthClass,
  getOffsetClass,
} from '@/utils/gridAdapter';

vi.mock('@/utils/gridAdapter', () => ({
  getColumnCount: vi.fn(() => 12),
  getRowClasses: vi.fn(() => 'row'),
  getWidthClass: vi.fn((width: number) => `col-${width}`),
  getOffsetClass: vi.fn((offset: number) => `offset-${offset}`),
  getDefaultViewport: vi.fn(() => 'md'),
}));

function makeColumn(id: number, title: string, overrides: Partial<EnrichedColumnNode> = {}): EnrichedColumnNode {
  return {
    id,
    title,
    blockSchema: {
      typeName: 'WeDevelop\\ElementalGrid\\Column',
      label: 'Column',
      actions: { edit: `/admin/elemental/edit/${id}` },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'column' as const,
    allowedTypes: null,
    children: null,
    gridSettings: {
      md: { width: 6, offset: 0, visible: true },
    },
    isCollapsed: false,
    toggle: vi.fn(),
    ...overrides,
  };
}

function makeRow(overrides: Partial<EnrichedRowNode> = {}): EnrichedRowNode {
  return {
    id: 20,
    title: 'Row',
    blockSchema: {
      typeName: 'WeDevelop\\ElementalGrid\\Row',
      label: 'Row',
      actions: { edit: '/admin/elemental/edit/20' },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'row',
    allowedTypes: null,
    children: null,
    isCollapsed: false,
    toggle: vi.fn(),
    ...overrides,
  };
}

describe('RowBlock', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getRowClasses).mockReturnValue('row');
    vi.mocked(getColumnCount).mockReturnValue(12);
    vi.mocked(getWidthClass).mockImplementation((width: number) => `col-${width}`);
    vi.mocked(getOffsetClass).mockImplementation((offset: number) => `offset-${offset}`);
  });

  it('renders title as an h3 heading', () => {
    const row = makeRow({ title: 'Main Row' });

    render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const heading = screen.getByRole('heading', { level: 3 });
    expect(heading.textContent).toBe('Main Row');
  });

  it('applies rowClasses from gridAdapter on the column container div', () => {
    const row = makeRow();

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const columnContainer = container.querySelector('.row');
    expect(columnContainer).not.toBeNull();
  });

  it('applies custom rowClasses value from gridAdapter', () => {
    vi.mocked(getRowClasses).mockReturnValue('columns is-multiline');
    const row = makeRow();

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const columnContainer = container.querySelector('.columns.is-multiline');
    expect(columnContainer).not.toBeNull();
  });

  it('renders column children as ColumnBlocks', () => {
    const row = makeRow({
      children: [
        makeColumn(10, 'Left Column'),
        makeColumn(11, 'Right Column'),
      ],
    });

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const badges = container.querySelectorAll('.column-block__badge');
    expect(badges.length).toBe(2);
  });

  it('renders EmptyState when children is null', () => {
    const row = makeRow({ children: null });

    render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('No columns')).toBeDefined();
  });

  it('renders EmptyState when children is empty array', () => {
    const row = makeRow({ children: [] });

    render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('No columns')).toBeDefined();
  });

  it('applies draft publication state modifier class', () => {
    const row = makeRow({
      statusFlags: { addedtodraft: { text: 'Draft', title: 'Item has not been published yet' } },
    });

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.row-block');
    expect(outer?.classList.contains('row-block--draft')).toBe(true);
  });

  it('applies published publication state modifier class', () => {
    const row = makeRow({
      statusFlags: {},
    });

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.row-block');
    expect(outer?.classList.contains('row-block--published')).toBe(true);
  });

  it('applies modified publication state modifier class', () => {
    const row = makeRow({
      statusFlags: { modified: { text: 'Modified', title: 'Item has unpublished changes' } },
    });

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.row-block');
    expect(outer?.classList.contains('row-block--modified')).toBe(true);
  });

  it('child columns resolve settings based on activeViewport from context', () => {
    const row = makeRow({
      children: [
        makeColumn(10, 'Column', {
          gridSettings: {
            md: { width: 6, offset: 0, visible: true },
            lg: { width: 4, offset: 0, visible: true },
          },
        }),
      ],
    });

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper('lg') },
    );

    // When activeViewport is "lg", the ColumnBlock should use lg settings (width 4)
    const badge = container.querySelector('.column-block__badge');
    expect(badge?.textContent).toBe('4/12');
  });

  it('child columns use getWidthClass and getOffsetClass from gridAdapter', () => {
    vi.mocked(getWidthClass).mockReturnValue('custom-w-8');
    vi.mocked(getOffsetClass).mockReturnValue('custom-o-2');

    const row = makeRow({
      children: [
        makeColumn(10, 'Column', {
          gridSettings: {
            md: { width: 8, offset: 2, visible: true },
          },
        }),
      ],
    });

    render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    expect(getWidthClass).toHaveBeenCalledWith(8);
    expect(getOffsetClass).toHaveBeenCalledWith(2);
  });

  it('child columns use getColumnCount from gridAdapter for badge display', () => {
    vi.mocked(getColumnCount).mockReturnValue(16);

    const row = makeRow({
      children: [
        makeColumn(10, 'Column', {
          gridSettings: {
            md: { width: 6, offset: 0, visible: true },
          },
        }),
      ],
    });

    const { container } = render(
      <RowBlock row={row} />,
      { wrapper: createViewportWrapper() },
    );

    // ColumnBlock shows width/columnCount, so with columnCount=16 and width=6
    const badge = container.querySelector('.column-block__badge');
    expect(badge?.textContent).toBe('6/16');
  });

  describe('collapse', () => {
    it('renders a collapse toggle button', () => {
      const row = makeRow();

      render(
        <RowBlock row={row} />,
        { wrapper: createViewportWrapper() },
      );

      expect(screen.getByTestId('collapse-toggle')).toBeDefined();
    });

    it('wires toggle to CollapseToggle onToggle', async () => {
      const toggle = vi.fn();
      const row = makeRow({ id: 77, toggle });
      const user = userEvent.setup();

      render(
        <RowBlock row={row} />,
        { wrapper: createViewportWrapper() },
      );

      await user.click(screen.getByTestId('collapse-toggle'));
      expect(toggle).toHaveBeenCalledOnce();
    });

    it('applies --collapsed modifier when collapsed', () => {
      const row = makeRow({ isCollapsed: true });

      const { container } = render(
        <RowBlock row={row} />,
        { wrapper: createViewportWrapper() },
      );

      const outer = container.querySelector('.row-block');
      expect(outer?.classList.contains('row-block--collapsed')).toBe(true);
    });

    it('does not apply --collapsed modifier when expanded', () => {
      const row = makeRow({ isCollapsed: false });

      const { container } = render(
        <RowBlock row={row} />,
        { wrapper: createViewportWrapper() },
      );

      const outer = container.querySelector('.row-block');
      expect(outer?.classList.contains('row-block--collapsed')).toBe(false);
    });
  });
});
