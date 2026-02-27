import {
  createElement,
  deleteElement,
  duplicateElement,
  fetchElementTree,
  publishElement,
  unpublishElement,
} from '@/api/endpoints';

const mockApiGet = vi.fn();
const mockApiPost = vi.fn();

vi.mock('@/api/client', () => ({
  apiGet: (...args: unknown[]) => mockApiGet(...args),
  apiPost: (...args: unknown[]) => mockApiPost(...args),
}));

vi.mock('@/api/config', () => ({
  getControllerLink: () => '/admin/elemental-grid',
}));

describe('endpoints', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    mockApiGet.mockReset();
    mockApiPost.mockReset();
  });

  describe('fetchElementTree', () => {
    it('calls GET with correct URL and validates response', async () => {
      const mockTree = {
        ElementalArea: [
          {
            id: 1,
            title: 'Section',
            containerType: 'section',
            allowedTypes: null,
            children: null,
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
      mockApiGet.mockResolvedValue(mockTree);

      const result = await fetchElementTree(42);

      expect(mockApiGet).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/readTree/42',
      );
      expect(result).toEqual(mockTree);
    });

    it('throws on invalid response shape', async () => {
      mockApiGet.mockResolvedValue('not-an-object');

      await expect(fetchElementTree(1)).rejects.toThrow();
    });
  });

  describe('createElement', () => {
    it('sends correct POST body', async () => {
      mockApiPost.mockResolvedValue(undefined);

      await createElement({
        elementClass: 'App\\MyElement',
        elementalAreaID: 10,
        insertAfterElementID: 5,
      });

      expect(mockApiPost).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/create',
        {
          elementClass: 'App\\MyElement',
          elementalAreaID: 10,
          insertAfterElementID: 5,
        },
      );
    });
  });

  describe('publishElement', () => {
    it('sends correct POST body', async () => {
      mockApiPost.mockResolvedValue(undefined);

      await publishElement(7);

      expect(mockApiPost).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/publish',
        { id: 7 },
      );
    });
  });

  describe('unpublishElement', () => {
    it('sends correct POST body', async () => {
      mockApiPost.mockResolvedValue(undefined);

      await unpublishElement(7);

      expect(mockApiPost).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/unpublish',
        { id: 7 },
      );
    });
  });

  describe('deleteElement', () => {
    it('sends correct POST body', async () => {
      mockApiPost.mockResolvedValue(undefined);

      await deleteElement(3);

      expect(mockApiPost).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/delete',
        { id: 3 },
      );
    });
  });

  describe('duplicateElement', () => {
    it('sends correct POST body', async () => {
      mockApiPost.mockResolvedValue(undefined);

      await duplicateElement(9);

      expect(mockApiPost).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/duplicate',
        { id: 9 },
      );
    });
  });
});
