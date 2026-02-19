import { render, screen } from '@testing-library/react';

import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import type { ColumnNode } from '@/types/elements';

function makeColumn(overrides: Partial<ColumnNode> = {}): ColumnNode {
  return {
    id: 10,
    title: 'Column',
    blockSchema: {
      typeName: 'WeDevelop\\ElementalGrid\\Column',
      actions: { edit: '/admin/elemental/edit/10' },
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
    containerType: 'column',
    allowedTypes: null,
    children: null,
    gridSettings: {
      md: { width: 6, offset: 0, visible: true },
    },
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

describe('ColumnBlock', () => {
  it('renders fraction badge for the active viewport', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('6/12')).toBeDefined();
  });

  it('applies width class from getWidthClass()', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('col-6')).toBe(true);
  });

  it('applies offset class from getOffsetClass() when offset > 0', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 4, offset: 2, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('offset-2')).toBe(true);
  });

  it('does not apply offset class when offset is 0', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
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
            actions: { edit: '/edit/101' },
            content: 'Text content',
          },
          obsoleteClassName: null,
          version: 1,
          isPublished: false,
          isLiveVersion: false,
          canDelete: true,
          canPublish: true,
          canUnpublish: false,
          canCreate: true,
          statusFlags: {},
        },
      ],
    });

    render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('Hero Banner')).toBeDefined();
    expect(screen.getByText('Text Block')).toBeDefined();
  });

  it('renders EmptyState when children is null', () => {
    const column = makeColumn({ children: null });

    render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('renders EmptyState when children is empty array', () => {
    const column = makeColumn({ children: [] });

    render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('applies hidden modifier when visible is false', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: false } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--hidden')).toBe(true);
  });

  it('does not apply hidden modifier when visible is true', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--hidden')).toBe(false);
  });

  it('shows "hidden" instead of fraction badge when not visible', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: false } },
    });

    render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(screen.getByText('hidden')).toBeDefined();
    expect(screen.queryByText('6/12')).toBeNull();
  });

  it('applies publication state modifier class for draft column', () => {
    const column = makeColumn({
      isPublished: false,
      isLiveVersion: false,
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--draft')).toBe(true);
  });

  it('applies publication state modifier class for published column', () => {
    const column = makeColumn({
      isPublished: true,
      isLiveVersion: true,
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--published')).toBe(true);
  });

  it('applies publication state modifier class for modified column', () => {
    const column = makeColumn({
      isPublished: true,
      isLiveVersion: false,
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--modified')).toBe(true);
  });

  it('falls back to full width when viewport key is missing from gridSettings', () => {
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="lg"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
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
      <ColumnBlock
        column={column}
        activeViewport="lg"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
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
      <ColumnBlock
        column={column}
        activeViewport="lg"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    const inner = container.querySelector('.column-block');
    expect(inner?.classList.contains('column-block--hidden')).toBe(false);
    expect(screen.getByText('12/12')).toBeDefined();
  });

  it('uses getWidthClass callback to determine the CSS class', () => {
    const customGetWidthClass = vi.fn().mockReturnValue('custom-col-8');
    const column = makeColumn({
      gridSettings: { md: { width: 8, offset: 0, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={customGetWidthClass}
        getOffsetClass={stubGetOffsetClass}
      />,
    );

    expect(customGetWidthClass).toHaveBeenCalledWith(8);
    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('custom-col-8')).toBe(true);
  });

  it('uses getOffsetClass callback to determine the offset CSS class', () => {
    const customGetOffsetClass = vi.fn().mockReturnValue('custom-offset-3');
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 3, visible: true } },
    });

    const { container } = render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={customGetOffsetClass}
      />,
    );

    expect(customGetOffsetClass).toHaveBeenCalledWith(3);
    const outerDiv = container.firstElementChild;
    expect(outerDiv?.classList.contains('custom-offset-3')).toBe(true);
  });

  it('does not call getOffsetClass when offset is 0', () => {
    const customGetOffsetClass = vi.fn().mockReturnValue('offset-0');
    const column = makeColumn({
      gridSettings: { md: { width: 6, offset: 0, visible: true } },
    });

    render(
      <ColumnBlock
        column={column}
        activeViewport="md"
        columnCount={COLUMN_COUNT}
        getWidthClass={stubGetWidthClass}
        getOffsetClass={customGetOffsetClass}
      />,
    );

    expect(customGetOffsetClass).not.toHaveBeenCalled();
  });
});
