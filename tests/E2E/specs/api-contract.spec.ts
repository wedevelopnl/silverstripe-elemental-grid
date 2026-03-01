import { expect, test } from '@playwright/test';
import {
  type ColumnNode,
  type SectionNode,
  elementTreeResponseSchema,
} from '@/types/elements';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

const API_BASE = '/admin/elemental-grid/api/readTree';

test.describe('API contract', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('readTree response validates against the Zod schema', async ({ request }) => {
    const fixture = await loadFixture(request, 'complex-page');
    const response = await request.get(`${API_BASE}/${fixture.pageId}`);

    expect(response.ok()).toBe(true);

    const body: unknown = await response.json();
    const result = elementTreeResponseSchema.safeParse(body);

    if (!result.success) {
      // Surface the Zod error for easy debugging
      throw new Error(
        `Schema validation failed:\n${JSON.stringify(result.error.issues, null, 2)}`,
      );
    }

    // Sanity: at least one section exists
    const areaKeys = Object.keys(result.data);
    expect(areaKeys.length).toBeGreaterThan(0);

    const firstArea = result.data[areaKeys[0]];
    expect(firstArea.length).toBeGreaterThan(0);
  });

  test('versioned state flags reflect fixture post-actions', async ({ request }) => {
    const fixture = await loadFixture(request, 'complex-page');
    const response = await request.get(`${API_BASE}/${fixture.pageId}`);
    const body: unknown = await response.json();
    const tree = elementTreeResponseSchema.parse(body);

    // Collect all containers and leaf elements across the tree
    type FlaggedNode = { title: string; statusFlags: Record<string, unknown> };
    const leaves: FlaggedNode[] = [];
    const containers: FlaggedNode[] = [];

    for (const sections of Object.values(tree)) {
      for (const section of sections) {
        if (!('containerType' in section) || section.containerType !== 'section') continue;
        const sectionNode = section as SectionNode;
        containers.push({ title: sectionNode.title, statusFlags: sectionNode.statusFlags });
        for (const row of sectionNode.children ?? []) {
          containers.push({ title: row.title, statusFlags: row.statusFlags });
          for (const col of row.children ?? []) {
            containers.push({ title: col.title, statusFlags: col.statusFlags });
            for (const leaf of col.children ?? []) {
              leaves.push({ title: leaf.title, statusFlags: leaf.statusFlags });
            }
          }
        }
      }
    }

    // Leaf assertions: publishRecursive → unpublish draft_leaf → modify modified_leaf
    const draft = leaves.find((l) => l.title === 'Draft Only Block');
    expect(draft, 'Draft Only Block not found in tree').toBeDefined();
    expect(draft!.statusFlags).toHaveProperty('addedtodraft');

    const published = leaves.find((l) => l.title === 'Published Block');
    expect(published, 'Published Block not found in tree').toBeDefined();
    expect(Object.keys(published!.statusFlags)).toHaveLength(0);

    const modified = leaves.find((l) => l.title.startsWith('Modified Text Block'));
    expect(modified, 'Modified Text Block not found in tree').toBeDefined();
    expect(modified!.statusFlags).toHaveProperty('modified');

    // Container assertions: modify row1 → unpublish section2 (cascades to descendants)
    const publishedSection = containers.find((c) => c.title === 'Main Section');
    expect(publishedSection, 'Main Section not found').toBeDefined();
    expect(Object.keys(publishedSection!.statusFlags)).toHaveLength(0);

    const modifiedRow = containers.find((c) => c.title.startsWith('First Row'));
    expect(modifiedRow, 'First Row not found').toBeDefined();
    expect(modifiedRow!.statusFlags).toHaveProperty('modified');

    const draftSection = containers.find((c) => c.title === 'Draft Section');
    expect(draftSection, 'Draft Section not found').toBeDefined();
    expect(draftSection!.statusFlags).toHaveProperty('addedtodraft');
  });

  test('column nodes include gridSettings with per-viewport structure', async ({ request }) => {
    const fixture = await loadFixture(request, 'complex-page');
    const response = await request.get(`${API_BASE}/${fixture.pageId}`);
    const body: unknown = await response.json();
    const tree = elementTreeResponseSchema.parse(body);

    // Collect all column nodes
    const columns: ColumnNode[] = [];
    for (const sections of Object.values(tree)) {
      for (const section of sections) {
        if (!('containerType' in section) || section.containerType !== 'section') continue;
        const sectionNode = section as SectionNode;
        for (const row of sectionNode.children ?? []) {
          for (const col of row.children ?? []) {
            columns.push(col);
          }
        }
      }
    }

    expect(columns.length).toBeGreaterThanOrEqual(2);

    const expectedViewports = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

    for (const col of columns) {
      // gridSettings must exist (enforced by Zod, but verify structure)
      expect(col.gridSettings).toBeDefined();

      for (const vp of expectedViewports) {
        const settings = col.gridSettings[vp];
        expect(settings, `Missing viewport "${vp}" in column "${col.title}"`).toBeDefined();
        expect(typeof settings.width).toBe('number');
        expect(typeof settings.offset).toBe('number');
        expect(typeof settings.visible).toBe('boolean');
      }
    }

    // Verify specific values from ComplexPage.yml for the left column (md viewport)
    const leftCol = columns.find((c) => c.title === 'Left Column');
    expect(leftCol, 'Left Column not found').toBeDefined();
    expect(leftCol!.gridSettings['md']).toEqual({ width: 8, offset: 0, visible: true });

    const rightCol = columns.find((c) => c.title === 'Right Column');
    expect(rightCol, 'Right Column not found').toBeDefined();
    expect(rightCol!.gridSettings['md']).toEqual({ width: 4, offset: 0, visible: true });
    expect(rightCol!.gridSettings['xs']).toEqual({ width: 12, offset: 0, visible: false });
  });
});
