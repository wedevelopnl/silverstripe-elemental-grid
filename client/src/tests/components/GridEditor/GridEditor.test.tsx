import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';

import GridEditor from '@/components/GridEditor/GridEditor';
import type { ElementTreeResponse } from '@/types/elements';

const mockFetchElementTree = vi.fn();

vi.mock('@/api/endpoints', () => ({
  fetchElementTree: (...args: unknown[]) => mockFetchElementTree(...args),
  reorderElement: vi.fn(),
}));

vi.mock('@/utils/gridAdapter', () => ({
  getViewports: vi.fn(() => [
    { key: 'xs', label: 'XS' },
    { key: 'sm', label: 'SM' },
    { key: 'md', label: 'MD' },
    { key: 'lg', label: 'LG' },
    { key: 'xl', label: 'XL' },
    { key: 'xxl', label: 'XXL' },
  ]),
  getDefaultViewport: vi.fn(() => 'md'),
  getColumnCount: vi.fn(() => 12),
  getRowClasses: vi.fn(() => 'row'),
  getWidthClass: vi.fn((width: number) => `col-${width}`),
  getOffsetClass: vi.fn((offset: number) => `offset-${offset}`),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
    },
  });

  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        {children}
      </QueryClientProvider>
    );
  };
}

const mockTree: ElementTreeResponse = {
  '42': [
    {
      id: 1,
      title: 'Main Section',
      containerType: 'section',
      allowedTypes: null,
      childAreaId: 100,
      children: [
        {
          id: 2,
          title: 'First Row',
          containerType: 'row',
          allowedTypes: null,
          childAreaId: 200,
          children: [
            {
              id: 3,
              title: 'Left Column',
              containerType: 'column',
              allowedTypes: null,
              childAreaId: 300,
              children: [
                {
                  id: 4,
                  title: 'Text Block',
                  blockSchema: { typeName: 'Content', label: 'Content', actions: { edit: '/edit/4' }, content: '' },
                  obsoleteClassName: null,
                  version: 1,
                  canDelete: true,
                  canPublish: true,
                  canUnpublish: false,
                  canCreate: true,
                  statusFlags: {},
                },
              ],
              gridSettings: {
                xs: { width: 12, offset: 0, visible: true },
                md: { width: 8, offset: 0, visible: true },
                lg: { width: 6, offset: 0, visible: true },
              },
              blockSchema: { typeName: 'Column', label: 'Column', actions: { edit: '/edit/3' }, content: '' },
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
            {
              id: 5,
              title: 'Right Column',
              containerType: 'column',
              allowedTypes: null,
              childAreaId: 301,
              children: null,
              gridSettings: {
                xs: { width: 12, offset: 0, visible: true },
                md: { width: 4, offset: 0, visible: true },
                lg: { width: 6, offset: 0, visible: true },
              },
              blockSchema: { typeName: 'Column', label: 'Column', actions: { edit: '/edit/5' }, content: '' },
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
          blockSchema: { typeName: 'Row', label: 'Row', actions: { edit: '/edit/2' }, content: '' },
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
      blockSchema: { typeName: 'Section', label: 'Section', actions: { edit: '/edit/1' }, content: '' },
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
};

const emptyTree: ElementTreeResponse = {
  '42': [],
};

const noSectionsTree: ElementTreeResponse = {
  '42': [
    {
      id: 99,
      title: 'Standalone Block',
      blockSchema: { typeName: 'Content', label: 'Content', actions: { edit: '/edit/99' }, content: '' },
      obsoleteClassName: null,
      version: 1,
      canDelete: true,
      canPublish: true,
      canUnpublish: false,
      canCreate: true,
      statusFlags: {},
    },
  ],
};

describe('GridEditor', () => {
  afterEach(() => {
    mockFetchElementTree.mockReset();
  });

  it('shows loading state when fetching', () => {
    mockFetchElementTree.mockReturnValue(new Promise(() => {}));

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    expect(screen.getByText('Loading elements...')).toBeDefined();
  });

  it('shows error message when fetch fails', async () => {
    mockFetchElementTree.mockRejectedValue(new Error('Network error'));

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText(/Failed to load elements/)).toBeDefined();
    });
  });

  it('renders viewport switcher when data loads', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByRole('group', { name: 'Viewport size' })).toBeDefined();
    });

    // All viewport buttons are rendered
    expect(screen.getByText('XS')).toBeDefined();
    expect(screen.getByText('SM')).toBeDefined();
    expect(screen.getByText('MD')).toBeDefined();
    expect(screen.getByText('LG')).toBeDefined();
    expect(screen.getByText('XL')).toBeDefined();
    expect(screen.getByText('XXL')).toBeDefined();
  });

  it('renders section blocks when data loads', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('Main Section')).toBeDefined();
    });

    const sectionBlocks = screen.getAllByTestId('section-block');
    expect(sectionBlocks.length).toBe(1);
  });

  it('renders empty state when tree has no sections (empty relation)', async () => {
    mockFetchElementTree.mockResolvedValue(emptyTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('No sections yet')).toBeDefined();
    });
  });

  it('renders empty state when tree has nodes but none are sections', async () => {
    mockFetchElementTree.mockResolvedValue(noSectionsTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('No sections yet')).toBeDefined();
    });
  });

  it('sets data-area-id attribute', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    const editorDiv = screen.getByTestId('grid-editor');
    expect(editorDiv.dataset.areaId).toBe('42');
  });

  it('sets data-page-id attribute when pageId is provided', () => {
    mockFetchElementTree.mockReturnValue(new Promise(() => {}));

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    const editorDiv = screen.getByTestId('grid-editor');
    expect(editorDiv.dataset.pageId).toBe('7');
  });

  it('omits data-page-id attribute when pageId is null', () => {
    render(<GridEditor areaId={42} pageId={null} />, {
      wrapper: createWrapper(),
    });

    const editorDiv = screen.getByTestId('grid-editor');
    expect(editorDiv.dataset.pageId).toBeUndefined();
  });

  it('updates column fraction badges when viewport is switched', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);
    const user = userEvent.setup();

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    // Wait for data to load — default viewport is "md"
    await waitFor(() => {
      expect(screen.getByText('Main Section')).toBeDefined();
    });

    // At md viewport: Left Column = 8/12, Right Column = 4/12
    expect(screen.getByText('8/12')).toBeDefined();
    expect(screen.getByText('4/12')).toBeDefined();

    // Switch to lg viewport
    await user.click(screen.getByText('LG'));

    // At lg viewport: Left Column = 6/12, Right Column = 6/12
    const badges = screen.getAllByText('6/12');
    expect(badges.length).toBe(2);
  });

  it('renders empty state when areaId is not present in the response', async () => {
    const treeForDifferentArea: ElementTreeResponse = {
      '99': [mockTree['42'][0]],
    };
    mockFetchElementTree.mockResolvedValue(treeForDifferentArea);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('No sections yet')).toBeDefined();
    });
  });

  it('does not render content area when still loading', () => {
    mockFetchElementTree.mockReturnValue(new Promise(() => {}));

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    expect(screen.queryByTestId('section-block')).toBeNull();
    expect(screen.queryByTestId('viewport-switcher')).toBeNull();
    expect(screen.queryByText('No sections yet')).toBeNull();
  });

  it('does not render DragOverlayContent when no drag is active', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('Main Section')).toBeDefined();
    });

    expect(screen.queryByTestId('drag-overlay-section')).toBeNull();
    expect(screen.queryByTestId('drag-overlay-row')).toBeNull();
    expect(screen.queryByTestId('drag-overlay-column')).toBeNull();
    expect(screen.queryByTestId('drag-overlay-element')).toBeNull();
  });
});
