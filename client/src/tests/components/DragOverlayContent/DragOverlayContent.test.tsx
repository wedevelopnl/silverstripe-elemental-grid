import { render, screen } from '@testing-library/react';

import DragOverlayContent from '@/components/DragOverlayContent/DragOverlayContent';
import type {
  SectionNode,
  RowNode,
  ColumnNode,
  SimpleElementNode,
} from '@/types/elements';

function makeElement(id: number, overrides: Partial<SimpleElementNode> = {}): SimpleElementNode {
  return {
    id,
    title: `Element ${id}`,
    blockSchema: {
      typeName: 'TextBlock',
      label: 'Text Block',
      actions: { edit: `/edit/${id}` },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    ...overrides,
  };
}

function makeColumn(id: number, overrides: Partial<ColumnNode> = {}): ColumnNode {
  return {
    id,
    title: `Column ${id}`,
    blockSchema: {
      typeName: 'Column',
      label: 'Column',
      actions: { edit: `/edit/${id}` },
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
    gridSettings: { md: { width: 6, offset: 0, visible: true } },
    ...overrides,
  };
}

function makeRow(id: number, overrides: Partial<RowNode> = {}): RowNode {
  return {
    id,
    title: `Row ${id}`,
    blockSchema: {
      typeName: 'Row',
      label: 'Row',
      actions: { edit: `/edit/${id}` },
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
    childAreaId: 200,
    ...overrides,
  };
}

function makeSection(id: number, overrides: Partial<SectionNode> = {}): SectionNode {
  return {
    id,
    title: `Section ${id}`,
    blockSchema: {
      typeName: 'Section',
      label: 'Section',
      actions: { edit: `/edit/${id}` },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'section',
    allowedTypes: null,
    children: null,
    childAreaId: 300,
    ...overrides,
  };
}

describe('DragOverlayContent', () => {
  it('renders section preview with title and row count', () => {
    const section = makeSection(1, {
      title: 'Hero Section',
      children: [makeRow(10), makeRow(11)],
    });

    render(<DragOverlayContent node={section} type="section" />);

    expect(screen.getByText('Hero Section')).toBeDefined();
    expect(screen.getByText('2 rows')).toBeDefined();
  });

  it('renders section with singular "row" for single child', () => {
    const section = makeSection(1, {
      title: 'Single Row Section',
      children: [makeRow(10)],
    });

    render(<DragOverlayContent node={section} type="section" />);

    expect(screen.getByText('1 row')).toBeDefined();
  });

  it('renders row preview with title and column count', () => {
    const row = makeRow(1, {
      title: 'Content Row',
      children: [makeColumn(10), makeColumn(11), makeColumn(12)],
    });

    render(<DragOverlayContent node={row} type="row" />);

    expect(screen.getByText('Content Row')).toBeDefined();
    expect(screen.getByText('3 columns')).toBeDefined();
  });

  it('renders row with singular "column" for single child', () => {
    const row = makeRow(1, {
      title: 'Single Col Row',
      children: [makeColumn(10)],
    });

    render(<DragOverlayContent node={row} type="row" />);

    expect(screen.getByText('1 column')).toBeDefined();
  });

  it('renders column preview with title', () => {
    const column = makeColumn(1, { title: 'Sidebar Column' });

    render(<DragOverlayContent node={column} type="column" />);

    expect(screen.getByText('Sidebar Column')).toBeDefined();
  });

  it('renders element preview with type label and title', () => {
    const element = makeElement(1, {
      title: 'Welcome Text',
      blockSchema: {
        typeName: 'TextBlock',
        label: 'Text Block',
        actions: { edit: '/edit/1' },
        content: '',
      },
    });

    render(<DragOverlayContent node={element} type="element" />);

    expect(screen.getByText('Text Block')).toBeDefined();
    expect(screen.getByText('Welcome Text')).toBeDefined();
  });

  it('handles null children gracefully for section (0 rows)', () => {
    const section = makeSection(1, {
      title: 'Empty Section',
      children: null,
    });

    render(<DragOverlayContent node={section} type="section" />);

    expect(screen.getByText('Empty Section')).toBeDefined();
    expect(screen.getByText('0 rows')).toBeDefined();
  });

  it('handles null children gracefully for row (0 columns)', () => {
    const row = makeRow(1, {
      title: 'Empty Row',
      children: null,
    });

    render(<DragOverlayContent node={row} type="row" />);

    expect(screen.getByText('Empty Row')).toBeDefined();
    expect(screen.getByText('0 columns')).toBeDefined();
  });

  it('applies the drag-overlay-content class', () => {
    const section = makeSection(1);

    const { container } = render(
      <DragOverlayContent node={section} type="section" />,
    );

    expect(container.querySelector('.drag-overlay-content')).not.toBeNull();
  });

  it('applies the type-specific modifier class', () => {
    const section = makeSection(1);

    const { container } = render(
      <DragOverlayContent node={section} type="section" />,
    );

    expect(
      container.querySelector('.drag-overlay-content--section'),
    ).not.toBeNull();
  });
});
