import { render, screen } from '@testing-library/react';

import ElementCard from '@/components/ElementCard/ElementCard';
import type { SimpleElementNode } from '@/types/elements';

function makeElement(overrides: Partial<SimpleElementNode> = {}): SimpleElementNode {
  return {
    id: 1,
    title: 'My Element',
    blockSchema: {
      typeName: String.raw`DNADesign\Elemental\Models\BaseElement`,
      actions: { edit: '/admin/elemental/edit/1' },
      content: 'Some preview text',
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
    ...overrides,
  };
}

describe('ElementCard', () => {
  it('renders the element title', () => {
    render(<ElementCard element={makeElement({ title: 'Hero Banner' })} />);

    expect(screen.getByText('Hero Banner')).toBeDefined();
  });

  it('renders "(untitled)" when title is empty', () => {
    render(<ElementCard element={makeElement({ title: '' })} />);

    expect(screen.getByText('(untitled)')).toBeDefined();
  });

  it('renders content preview from blockSchema.content', () => {
    const element = makeElement({
      blockSchema: {
        typeName: 'Content',
        actions: { edit: '/edit/1' },
        content: 'A detailed paragraph about widgets.',
      },
    });

    render(<ElementCard element={element} />);

    expect(screen.getByText('A detailed paragraph about widgets.')).toBeDefined();
  });

  it('renders "No preview available" when content is empty', () => {
    const element = makeElement({
      blockSchema: {
        typeName: 'Content',
        actions: { edit: '/edit/1' },
        content: '',
      },
    });

    render(<ElementCard element={element} />);

    expect(screen.getByText('No preview available')).toBeDefined();
  });

  it('strips PHP namespace from typeName to show short type name', () => {
    const element = makeElement({
      blockSchema: {
        typeName: String.raw`DNADesign\Elemental\Models\BaseElement`,
        actions: { edit: '/edit/1' },
        content: 'preview',
      },
    });

    render(<ElementCard element={element} />);

    expect(screen.getByText('BaseElement')).toBeDefined();
    expect(screen.queryByText(String.raw`DNADesign\Elemental\Models\BaseElement`)).toBeNull();
  });

  it('renders unqualified typeName unchanged', () => {
    const element = makeElement({
      blockSchema: {
        typeName: 'Content',
        actions: { edit: '/edit/1' },
        content: 'preview',
      },
    });

    render(<ElementCard element={element} />);

    expect(screen.getByText('Content')).toBeDefined();
  });

  it('applies "element-card--draft" class for unpublished elements', () => {
    const element = makeElement({ isPublished: false, isLiveVersion: false });

    const { container } = render(<ElementCard element={element} />);

    const card = container.querySelector('.element-card');
    expect(card?.classList.contains('element-card--draft')).toBe(true);
  });

  it('applies "element-card--published" class for published live elements', () => {
    const element = makeElement({ isPublished: true, isLiveVersion: true });

    const { container } = render(<ElementCard element={element} />);

    const card = container.querySelector('.element-card');
    expect(card?.classList.contains('element-card--published')).toBe(true);
  });

  it('applies "element-card--modified" class for published but not live elements', () => {
    const element = makeElement({ isPublished: true, isLiveVersion: false });

    const { container } = render(<ElementCard element={element} />);

    const card = container.querySelector('.element-card');
    expect(card?.classList.contains('element-card--modified')).toBe(true);
  });

  it('does not render any interactive elements', () => {
    const { container } = render(<ElementCard element={makeElement()} />);

    expect(container.querySelectorAll('button').length).toBe(0);
    expect(container.querySelectorAll('a').length).toBe(0);
    expect(container.querySelectorAll('input').length).toBe(0);
    expect(container.querySelectorAll('select').length).toBe(0);
    expect(container.querySelectorAll('textarea').length).toBe(0);
  });
});
