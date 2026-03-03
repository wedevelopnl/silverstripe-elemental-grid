import {
  createElement,
  deleteElement,
  duplicateElement,
  fetchElementTree,
  publishElement,
  reorderElement,
  unpublishElement,
} from '@/api/endpoints';

const mockApiGet = vi.fn();
const mockApiPost = vi.fn();
const mockApiPatch = vi.fn();
const mockApiDelete = vi.fn();

vi.mock('@/api/client', () => ({
  apiGet: (...args: unknown[]) => mockApiGet(...args),
  apiPost: (...args: unknown[]) => mockApiPost(...args),
  apiPatch: (...args: unknown[]) => mockApiPatch(...args),
  apiDelete: (...args: unknown[]) => mockApiDelete(...args),
}));

vi.mock('@/api/config', () => ({
  getControllerLink: () => '/admin/elemental-grid',
}));

describe('endpoints', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    mockApiGet.mockReset();
    mockApiPost.mockReset();
    mockApiPatch.mockReset();
    mockApiDelete.mockReset();
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
            childAreaId: null,
            blockSchema: { typeName: 'Section', label: 'Section', actions: { edit: '/edit/1' }, content: '' },
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
    it('sends correct PATCH body', async () => {
      mockApiPatch.mockResolvedValue(undefined);

      await publishElement(7);

      expect(mockApiPatch).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/publish',
        { id: 7 },
      );
    });
  });

  describe('unpublishElement', () => {
    it('sends correct PATCH body', async () => {
      mockApiPatch.mockResolvedValue(undefined);

      await unpublishElement(7);

      expect(mockApiPatch).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/unpublish',
        { id: 7 },
      );
    });
  });

  describe('deleteElement', () => {
    it('sends correct DELETE body', async () => {
      mockApiDelete.mockResolvedValue(undefined);

      await deleteElement(3);

      expect(mockApiDelete).toHaveBeenCalledWith(
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

  describe('reorderElement', () => {
    it('sends correct PATCH body with afterElementID', async () => {
      mockApiPatch.mockResolvedValue(undefined);

      await reorderElement({
        elementID: 5,
        targetAreaID: 10,
        afterElementID: 3,
      });

      expect(mockApiPatch).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/reorder',
        {
          elementID: 5,
          targetAreaID: 10,
          afterElementID: 3,
        },
      );
    });

    it('sends null afterElementID for first position', async () => {
      mockApiPatch.mockResolvedValue(undefined);

      await reorderElement({
        elementID: 5,
        targetAreaID: 10,
        afterElementID: null,
      });

      expect(mockApiPatch).toHaveBeenCalledWith(
        '/admin/elemental-grid/api/reorder',
        {
          elementID: 5,
          targetAreaID: 10,
          afterElementID: null,
        },
      );
    });
  });
});
