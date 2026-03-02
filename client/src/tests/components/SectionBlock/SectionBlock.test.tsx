import { render, screen } from '@testing-library/react';

import SectionBlock from '@/components/SectionBlock/SectionBlock';
import type { RowNode, SectionNode } from '@/types/elements';
import { createViewportWrapper } from '@/tests/helpers/viewportTestUtils';
import {
  getRowClasses,
  getWidthClass,
  getOffsetClass,
} from '@/utils/gridAdapter';
import { useCollapse } from '@/hooks/useCollapse';

vi.mock('@/utils/gridAdapter', () => ({
  getColumnCount: vi.fn(() => 12),
  getRowClasses: vi.fn(() => 'row'),
  getWidthClass: vi.fn((width: number) => `col-${width}`),
  getOffsetClass: vi.fn((offset: number) => `offset-${offset}`),
  getDefaultViewport: vi.fn(() => 'md'),
}));

vi.mock('@/hooks/useCollapse', () => ({
  useCollapse: vi.fn(() => ({ isCollapsed: false, toggle: vi.fn() })),
}));

function makeSection(overrides: Partial<SectionNode> = {}): SectionNode {
  return {
    id: 1,
    title: 'Section',
    blockSchema: {
      typeName: 'WeDevelop\\ElementalGrid\\Section',
      label: 'Section',
      actions: { edit: '/admin/elemental/edit/1' },
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
    ...overrides,
  };
}

function makeRow(id: number, title: string, overrides: Partial<RowNode> = {}): RowNode {
  return {
    id,
    title,
    blockSchema: {
      typeName: 'WeDevelop\\ElementalGrid\\Row',
      label: 'Row',
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
    containerType: 'row',
    allowedTypes: null,
    children: null,
    ...overrides,
  };
}

describe('SectionBlock', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getRowClasses).mockReturnValue('row');
    vi.mocked(getWidthClass).mockImplementation((width: number) => `col-${width}`);
    vi.mocked(getOffsetClass).mockImplementation((offset: number) => `offset-${offset}`);
  });

  it('renders as a <section> element', () => {
    const section = makeSection();

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    expect(container.querySelector('section.section-block')).not.toBeNull();
  });

  it('renders title as an h2 heading', () => {
    const section = makeSection({ title: 'Hero Section' });

    render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const heading = screen.getByRole('heading', { level: 2 });
    expect(heading.textContent).toBe('Hero Section');
  });

  it('renders row children as RowBlocks', () => {
    const section = makeSection({
      children: [
        makeRow(10, 'First Row'),
        makeRow(11, 'Second Row'),
      ],
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const rowBlocks = container.querySelectorAll('.row-block');
    expect(rowBlocks.length).toBe(2);
  });

  it('renders EmptyState when children is null', () => {
    const section = makeSection({ children: null });

    render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('No rows')).toBeDefined();
  });

  it('renders EmptyState when children is empty array', () => {
    const section = makeSection({ children: [] });

    render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    expect(screen.getByText('No rows')).toBeDefined();
  });

  it('applies draft publication state modifier class', () => {
    const section = makeSection({
      statusFlags: { addedtodraft: { text: 'Draft', title: 'Item has not been published yet' } },
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.section-block');
    expect(outer?.classList.contains('section-block--draft')).toBe(true);
  });

  it('applies published publication state modifier class', () => {
    const section = makeSection({
      statusFlags: {},
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.section-block');
    expect(outer?.classList.contains('section-block--published')).toBe(true);
  });

  it('applies modified publication state modifier class', () => {
    const section = makeSection({
      statusFlags: { modified: { text: 'Modified', title: 'Item has unpublished changes' } },
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.section-block');
    expect(outer?.classList.contains('section-block--modified')).toBe(true);
  });

  it('child columns resolve settings based on activeViewport from context', () => {
    const section = makeSection({
      children: [makeRow(10, 'Row')],
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper('lg') },
    );

    const rowBlocks = container.querySelectorAll('.row-block');
    expect(rowBlocks.length).toBe(1);
  });

  it('rows apply rowClasses from gridAdapter', () => {
    vi.mocked(getRowClasses).mockReturnValue('columns is-multiline');

    const section = makeSection({
      children: [makeRow(10, 'Row')],
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const columnContainer = container.querySelector('.columns.is-multiline');
    expect(columnContainer).not.toBeNull();
  });

  it('nested columns use getWidthClass and getOffsetClass from gridAdapter', () => {
    vi.mocked(getWidthClass).mockReturnValue('custom-w-8');
    vi.mocked(getOffsetClass).mockReturnValue('custom-o-2');

    const section = makeSection({
      children: [
        makeRow(10, 'Row', {
          children: [
            {
              id: 30,
              title: 'Column',
              blockSchema: {
                typeName: 'WeDevelop\\ElementalGrid\\Column',
                label: 'Column',
                actions: { edit: '/admin/elemental/edit/30' },
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
                md: { width: 8, offset: 2, visible: true },
              },
            },
          ],
        }),
      ],
    });

    render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    expect(getWidthClass).toHaveBeenCalledWith(8);
    expect(getOffsetClass).toHaveBeenCalledWith(2);
  });

  it('renders rows in the body area within section-block__body', () => {
    const section = makeSection({
      children: [makeRow(10, 'First Row')],
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const body = container.querySelector('.section-block__body');
    expect(body).not.toBeNull();

    const rowsInsideBody = body?.querySelectorAll('.row-block');
    expect(rowsInsideBody?.length).toBe(1);
  });

  describe('collapse', () => {
    it('renders a collapse toggle button', () => {
      const section = makeSection();

      render(
        <SectionBlock section={section} />,
        { wrapper: createViewportWrapper() },
      );

      expect(screen.getByTestId('collapse-toggle')).toBeDefined();
    });

    it('passes the section ID to useCollapse', () => {
      const section = makeSection({ id: 42 });

      render(
        <SectionBlock section={section} />,
        { wrapper: createViewportWrapper() },
      );

      expect(useCollapse).toHaveBeenCalledWith(42);
    });

    it('hides body when collapsed', () => {
      vi.mocked(useCollapse).mockReturnValue({ isCollapsed: true, toggle: vi.fn() });

      const section = makeSection({
        children: [makeRow(10, 'First Row')],
      });

      const { container } = render(
        <SectionBlock section={section} />,
        { wrapper: createViewportWrapper() },
      );

      const outer = container.querySelector('.section-block');
      expect(outer?.classList.contains('section-block--collapsed')).toBe(true);
    });

    it('shows body when expanded', () => {
      vi.mocked(useCollapse).mockReturnValue({ isCollapsed: false, toggle: vi.fn() });

      const section = makeSection({
        children: [makeRow(10, 'First Row')],
      });

      const { container } = render(
        <SectionBlock section={section} />,
        { wrapper: createViewportWrapper() },
      );

      const outer = container.querySelector('.section-block');
      expect(outer?.classList.contains('section-block--collapsed')).toBe(false);
    });
  });
});
