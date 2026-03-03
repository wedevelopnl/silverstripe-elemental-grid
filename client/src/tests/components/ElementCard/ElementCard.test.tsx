import { render, screen } from '@testing-library/react';

import ElementCard from '@/components/ElementCard/ElementCard';
import type { SimpleElementNode } from '@/types/elements';
import { createDndWrapper } from '@/tests/helpers/dndTestUtils';

function makeElement(overrides: Partial<SimpleElementNode> = {}): SimpleElementNode {
  return {
    id: 1,
    title: 'My Element',
    blockSchema: {
      typeName: String.raw`DNADesign\Elemental\Models\BaseElement`,
      label: 'Base Element',
      actions: { edit: '/admin/elemental/edit/1' },
      content: 'Some preview text',
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

describe('ElementCard', () => {
  it('renders the element title as an h4 heading', () => {
    render(<ElementCard element={makeElement({ title: 'Hero Banner' })} />, {
      wrapper: createDndWrapper(),
    });

    const heading = screen.getByRole('heading', { level: 4 });
    expect(heading.textContent).toBe('Hero Banner');
  });

  it('renders content preview from blockSchema.content', () => {
    const element = makeElement({
      blockSchema: {
        typeName: 'Content',
        label: 'Content',
        actions: { edit: '/edit/1' },
        content: 'A detailed paragraph about widgets.',
      },
    });

    render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    expect(screen.getByText('A detailed paragraph about widgets.')).toBeDefined();
  });

  it('renders "No preview available" when content is empty', () => {
    const element = makeElement({
      blockSchema: {
        typeName: 'Content',
        label: 'Content',
        actions: { edit: '/edit/1' },
        content: '',
      },
    });

    render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    expect(screen.getByText('No preview available')).toBeDefined();
  });

  it('renders blockSchema.label as the type display name', () => {
    const element = makeElement({
      blockSchema: {
        typeName: String.raw`DNADesign\Elemental\Models\BaseElement`,
        label: 'Content Block',
        actions: { edit: '/edit/1' },
        content: 'preview',
      },
    });

    render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    expect(screen.getByText('Content Block')).toBeDefined();
  });

  it('applies element-card__content--empty class when content is empty', () => {
    const element = makeElement({
      blockSchema: {
        typeName: 'Content',
        label: 'Content',
        actions: { edit: '/edit/1' },
        content: '',
      },
    });

    const { container } = render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    expect(
      container.querySelector('.element-card__content')?.classList.contains('element-card__content--empty'),
    ).toBe(true);
  });

  it('does not apply element-card__content--empty class when content is non-empty', () => {
    const { container } = render(<ElementCard element={makeElement()} />, {
      wrapper: createDndWrapper(),
    });

    expect(
      container.querySelector('.element-card__content')?.classList.contains('element-card__content--empty'),
    ).toBe(false);
  });

  it('applies "element-card--draft" class for draft elements', () => {
    const element = makeElement({ statusFlags: { addedtodraft: { text: 'Draft', title: 'Item has not been published yet' } } });

    const { container } = render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    const card = container.querySelector('.element-card');
    expect(card?.classList.contains('element-card--draft')).toBe(true);
  });

  it('applies "element-card--published" class for published elements', () => {
    const element = makeElement({ statusFlags: {} });

    const { container } = render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    const card = container.querySelector('.element-card');
    expect(card?.classList.contains('element-card--published')).toBe(true);
  });

  it('applies "element-card--modified" class for modified elements', () => {
    const element = makeElement({ statusFlags: { modified: { text: 'Modified', title: 'Item has unpublished changes' } } });

    const { container } = render(<ElementCard element={element} />, {
      wrapper: createDndWrapper(),
    });

    const card = container.querySelector('.element-card');
    expect(card?.classList.contains('element-card--modified')).toBe(true);
  });

  it('renders only the drag handle button as an interactive element', () => {
    const { container } = render(<ElementCard element={makeElement()} />, {
      wrapper: createDndWrapper(),
    });

    expect(container.querySelectorAll('button').length).toBe(1);
    expect(container.querySelectorAll('a').length).toBe(0);
    expect(container.querySelectorAll('input').length).toBe(0);
    expect(container.querySelectorAll('select').length).toBe(0);
    expect(container.querySelectorAll('textarea').length).toBe(0);
  });

  it('renders a drag handle', () => {
    render(<ElementCard element={makeElement({ title: 'Hero Banner' })} />, {
      wrapper: createDndWrapper(),
    });

    const handle = screen.getByTestId('drag-handle');
    expect(handle).toBeDefined();
    expect(handle.getAttribute('aria-label')).toBe('Move Hero Banner');
  });
});
