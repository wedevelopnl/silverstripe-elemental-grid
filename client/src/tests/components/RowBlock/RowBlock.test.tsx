import { render, screen } from '@testing-library/react';

import RowBlock from '@/components/RowBlock/RowBlock';
import type { ColumnNode, RowNode } from '@/types/elements';

function makeRow(overrides: Partial<RowNode> = {}): RowNode {
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
    isPublished: true,
    isLiveVersion: true,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'row',
    allowedTypes: null,
    children: null,
    ...overrides,
  };
}

const COLUMN_COUNT = 12;

function stubGetWidthClass(width: number): string {
  return `col-${width}`;
}

function stubGetOffsetClass(offset: number): string {
  return `offset-${offset}`;
}

function makeColumn(id: number, title: string, overrides: Partial<ColumnNode> = {}) {
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
    isPublished: true,
    isLiveVersion: true,
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
    ...overrides,
  };
}

describe('RowBlock', () => {
  it('renders title as a small muted label', () => {
    const row = makeRow({ title: 'Main Row' });

    const { container } = render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const titleElement = container.querySelector('.row-block__title');
    expect(titleElement).not.toBeNull();
    expect(titleElement?.textContent).toBe('Main Row');
  });

  it('applies rowClasses prop on the column container div', () => {
    const row = makeRow();

    const { container } = render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const columnContainer = container.querySelector('.row');
    expect(columnContainer).not.toBeNull();
  });

  it('applies custom rowClasses value (not just "row")', () => {
    const row = makeRow();

    const { container } = render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="columns is-multiline"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
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
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    // ColumnBlocks render a badge with width/columnCount fraction
    const badges = container.querySelectorAll('.column-block__badge');
    expect(badges.length).toBe(2);
  });

  it('renders EmptyState when children is null', () => {
    const row = makeRow({ children: null });

    render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('No columns')).toBeDefined();
  });

  it('renders EmptyState when children is empty array', () => {
    const row = makeRow({ children: [] });

    render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('No columns')).toBeDefined();
  });

  it('applies draft publication state modifier class', () => {
    const row = makeRow({
      isPublished: false,
      isLiveVersion: false,
    });

    const { container } = render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const outer = container.querySelector('.row-block');
    expect(outer?.classList.contains('row-block--draft')).toBe(true);
  });

  it('applies published publication state modifier class', () => {
    const row = makeRow({
      isPublished: true,
      isLiveVersion: true,
    });

    const { container } = render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const outer = container.querySelector('.row-block');
    expect(outer?.classList.contains('row-block--published')).toBe(true);
  });

  it('applies modified publication state modifier class', () => {
    const row = makeRow({
      isPublished: true,
      isLiveVersion: false,
    });

    const { container } = render(
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const outer = container.querySelector('.row-block');
    expect(outer?.classList.contains('row-block--modified')).toBe(true);
  });

  it('passes activeViewport through to ColumnBlocks', () => {
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
      <RowBlock
        row={row}
        activeViewport="lg"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    // When activeViewport is "lg", the ColumnBlock should use lg settings (width 4)
    const badge = container.querySelector('.column-block__badge');
    expect(badge?.textContent).toBe('4/12');
  });

  it('passes getWidthClass and getOffsetClass through to ColumnBlocks', () => {
    const customGetWidthClass = vi.fn().mockReturnValue('custom-w-8');
    const customGetOffsetClass = vi.fn().mockReturnValue('custom-o-2');

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
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        rowClasses="row"
        getWidthClass={customGetWidthClass}
        getOffsetClass={customGetOffsetClass}
      />,
    );

    expect(customGetWidthClass).toHaveBeenCalledWith(8);
    expect(customGetOffsetClass).toHaveBeenCalledWith(2);
  });

  it('passes columnCount through to ColumnBlocks', () => {
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
      <RowBlock
        row={row}
        activeViewport="md"
        columnCount={16}
        rowClasses="row"
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    // ColumnBlock shows width/columnCount, so with columnCount=16 and width=6
    const badge = container.querySelector('.column-block__badge');
    expect(badge?.textContent).toBe('6/16');
  });
});
