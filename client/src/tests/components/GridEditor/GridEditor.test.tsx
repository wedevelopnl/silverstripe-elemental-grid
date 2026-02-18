import { render, screen } from '@testing-library/react';

import GridEditor from '@/components/GridEditor/GridEditor';

describe('GridEditor', () => {
  it('renders with area ID displayed', () => {
    render(<GridEditor areaId={42} pageId={7} />);

    expect(screen.getByText('Grid editor for area 42')).toBeDefined();
  });

  it('sets data-area-id attribute', () => {
    const { container } = render(<GridEditor areaId={42} pageId={7} />);
    const editorDiv = container.querySelector('.grid-editor');

    expect(editorDiv?.getAttribute('data-area-id')).toBe('42');
  });

  it('sets data-page-id attribute when pageId is provided', () => {
    const { container } = render(<GridEditor areaId={42} pageId={7} />);
    const editorDiv = container.querySelector('.grid-editor');

    expect(editorDiv?.getAttribute('data-page-id')).toBe('7');
  });

  it('omits data-page-id attribute when pageId is null', () => {
    const { container } = render(<GridEditor areaId={42} pageId={null} />);
    const editorDiv = container.querySelector('.grid-editor');

    expect(editorDiv?.hasAttribute('data-page-id')).toBe(false);
  });
});
