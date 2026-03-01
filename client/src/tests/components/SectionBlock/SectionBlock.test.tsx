import { render, screen } from '@testing-library/react';

import SectionBlock from '@/components/SectionBlock/SectionBlock';
import type { RowNode, SectionNode } from '@/types/elements';
import { createViewportWrapper } from '@/tests/helpers/viewportTestUtils';

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
    isPublished: true,
    isLiveVersion: true,
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

describe('SectionBlock', () => {
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
      isPublished: false,
      isLiveVersion: false,
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
      isPublished: true,
      isLiveVersion: true,
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
      isPublished: true,
      isLiveVersion: false,
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper() },
    );

    const outer = container.querySelector('.section-block');
    expect(outer?.classList.contains('section-block--modified')).toBe(true);
  });

  it('passes activeViewport through to RowBlocks via context', () => {
    const section = makeSection({
      children: [makeRow(10, 'Row')],
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper({ activeViewport: 'lg' }) },
    );

    const rowBlocks = container.querySelectorAll('.row-block');
    expect(rowBlocks.length).toBe(1);
  });

  it('passes rowClasses through to RowBlocks via context', () => {
    const section = makeSection({
      children: [makeRow(10, 'Row')],
    });

    const { container } = render(
      <SectionBlock section={section} />,
      { wrapper: createViewportWrapper({ rowClasses: 'columns is-multiline' }) },
    );

    const columnContainer = container.querySelector('.columns.is-multiline');
    expect(columnContainer).not.toBeNull();
  });

  it('passes getWidthClass and getOffsetClass through to RowBlocks via context', () => {
    const customGetWidthClass = vi.fn().mockReturnValue('custom-w-8');
    const customGetOffsetClass = vi.fn().mockReturnValue('custom-o-2');

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
                md: { width: 8, offset: 2, visible: true },
              },
            },
          ],
        }),
      ],
    });

    render(
      <SectionBlock section={section} />,
      {
        wrapper: createViewportWrapper({
          getWidthClass: customGetWidthClass,
          getOffsetClass: customGetOffsetClass,
        }),
      },
    );

    expect(customGetWidthClass).toHaveBeenCalledWith(8);
    expect(customGetOffsetClass).toHaveBeenCalledWith(2);
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
});
