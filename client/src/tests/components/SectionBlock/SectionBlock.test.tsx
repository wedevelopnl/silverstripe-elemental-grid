import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import SectionBlock from '@/components/SectionBlock/SectionBlock';
import type { EnrichedSectionNode, EnrichedRowNode } from '@/types/enriched';
import { createViewportWrapper } from '@/tests/helpers/viewportTestUtils';
import {
  getRowClasses,
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

function makeRow(id: number, title: string, overrides: Partial<EnrichedRowNode> = {}): EnrichedRowNode {
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
    isCollapsed: false,
    toggle: vi.fn(),
    ...overrides,
  };
}

function makeSection(overrides: Partial<EnrichedSectionNode> = {}): EnrichedSectionNode {
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
    isCollapsed: false,
    toggle: vi.fn(),
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
              isCollapsed: false,
              toggle: vi.fn(),
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

    it('wires toggle to CollapseToggle onToggle', async () => {
      const toggle = vi.fn();
      const section = makeSection({ toggle });
      const user = userEvent.setup();

      render(
        <SectionBlock section={section} />,
        { wrapper: createViewportWrapper() },
      );

      await user.click(screen.getByTestId('collapse-toggle'));
      expect(toggle).toHaveBeenCalledOnce();
    });

    it('hides body when collapsed', () => {
      const section = makeSection({
        isCollapsed: true,
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
      const section = makeSection({
        isCollapsed: false,
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
