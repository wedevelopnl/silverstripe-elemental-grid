import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';

import GridEditor from '@/components/GridEditor/GridEditor';
import type { ElementTreeResponse } from '@/types/elements';

const mockFetchElementTree = vi.fn();

vi.mock('@/api/endpoints', () => ({
  fetchElementTree: (...args: unknown[]) => mockFetchElementTree(...args),
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
  ElementalArea: [
    {
      id: 1,
      title: 'Main Section',
      containerType: 'section',
      allowedTypes: null,
      children: [
        {
          id: 2,
          title: 'First Row',
          containerType: 'row',
          allowedTypes: null,
          children: [
            {
              id: 3,
              title: 'Left Column',
              containerType: 'column',
              allowedTypes: null,
              children: [
                {
                  id: 4,
                  title: 'Text Block',
                  blockSchema: { typeName: 'Content', actions: { edit: '/edit/4' }, content: '' },
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
              gridSettings: {
                xs: { width: 12, offset: 0, visible: true },
                sm: { width: 12, offset: 0, visible: true },
                md: { width: 12, offset: 0, visible: true },
                lg: { width: 12, offset: 0, visible: true },
                xl: { width: 12, offset: 0, visible: true },
              },
              blockSchema: { typeName: 'Column', actions: { edit: '/edit/3' }, content: '' },
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
          blockSchema: { typeName: 'Row', actions: { edit: '/edit/2' }, content: '' },
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
      blockSchema: { typeName: 'Section', actions: { edit: '/edit/1' }, content: '' },
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

  it('renders element tree when data loads', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('Main Section')).toBeDefined();
    });

    expect(screen.getByText('First Row')).toBeDefined();
    expect(screen.getByText('Left Column')).toBeDefined();
    expect(screen.getByText('Text Block')).toBeDefined();
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

  it('sets data-area-id attribute', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    const { container } = render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    const editorDiv = container.querySelector('.grid-editor');
    expect(editorDiv?.getAttribute('data-area-id')).toBe('42');
  });

  it('sets data-page-id attribute when pageId is provided', () => {
    mockFetchElementTree.mockReturnValue(new Promise(() => {}));

    const { container } = render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    const editorDiv = container.querySelector('.grid-editor');
    expect(editorDiv?.getAttribute('data-page-id')).toBe('7');
  });

  it('omits data-page-id attribute when pageId is null', () => {
    const { container } = render(<GridEditor areaId={42} pageId={null} />, {
      wrapper: createWrapper(),
    });

    const editorDiv = container.querySelector('.grid-editor');
    expect(editorDiv?.hasAttribute('data-page-id')).toBe(false);
  });

  it('displays container type badges for containers', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('[section]')).toBeDefined();
    });

    expect(screen.getByText('[row]')).toBeDefined();
    expect(screen.getByText('[column]')).toBeDefined();
    expect(screen.getByText('[Content]')).toBeDefined();
  });

  it('renders relation name heading', async () => {
    mockFetchElementTree.mockResolvedValue(mockTree);

    render(<GridEditor areaId={42} pageId={7} />, {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(screen.getByText('ElementalArea')).toBeDefined();
    });
  });
});
