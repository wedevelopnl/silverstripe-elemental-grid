import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';
import { performDrag, startDrag } from '../helpers/drag';

/** Load the DnD fixture and navigate to the CMS page editor. */
async function setupPage(page: import('@playwright/test').Page) {
  const fixture = await loadFixture(page.request, 'drag-and-drop');
  await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
  await expect(
    page.getByTestId('grid-editor-loading'),
  ).toBeHidden({ timeout: 15_000 });
  return fixture;
}

/** Get a drag handle by its aria-label (e.g. "Move Block 1"). */
function dragHandle(page: import('@playwright/test').Page, name: string) {
  return page.locator(`[data-testid="drag-handle"][aria-label="Move ${name}"]`);
}

/**
 * Get a column block by the title it was given in the fixture.
 * Columns don't render their title as visible text, so we match
 * via the drag handle's aria-label inside the column.
 */
function columnByTitle(parent: import('@playwright/test').Locator, page: import('@playwright/test').Page, title: string) {
  return parent.getByTestId('column-block').filter({
    has: page.locator(`[aria-label="Move ${title}"]`),
  });
}

/** Get all element cards within a container locator, in DOM order. */
function elementCards(container: import('@playwright/test').Locator) {
  return container.locator('.element-card');
}

/** Get all element card title locators within a container. */
function elementTitleLocators(container: import('@playwright/test').Locator) {
  return container.locator('.element-card .element-card__title');
}

test.describe('Drag and drop', () => {
  // The DnD fixture renders a deep hierarchy (~900px tall) that exceeds the
  // default Desktop Chrome viewport (720px). A taller viewport ensures all
  // drag handles are reachable by page.mouse without mid-drag scrolling.
  test.use({ viewport: { width: 1280, height: 1400 } });

  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  // --- Visual feedback ---

  test('drag overlay appears with correct content during element drag', async ({ page }) => {
    await setupPage(page);

    const source = dragHandle(page, 'Block 1');
    const target = dragHandle(page, 'Block 2');

    const drag = await startDrag(page, source, target);

    // Drag overlay should appear with the element type modifier
    const overlay = page.locator('.drag-overlay-content--element');
    await expect(overlay).toBeVisible();
    await expect(overlay.locator('.drag-overlay-content__title')).toHaveText('Block 1');

    // Source element should be dimmed (opacity: 0.3)
    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1 = sectionAlpha.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const colA1A = columnByTitle(rowA1, page, 'Column A1-A');
    const sourceCard = colA1A.locator('.element-card').first();
    await expect(sourceCard).toHaveCSS('opacity', '0.3');

    await drag.release();
  });

  test('drop target highlights during row drag', async ({ page }) => {
    await setupPage(page);

    const source = dragHandle(page, 'Row Alpha-1');
    const target = dragHandle(page, 'Row Alpha-2');

    const drag = await startDrag(page, source, target);

    // The row overlay should appear
    const overlay = page.locator('.drag-overlay-content--row');
    await expect(overlay).toBeVisible();

    await drag.release();
  });

  // --- Reorder operations ---

  test('reorder elements within column', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1 = sectionAlpha.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const colA1A = columnByTitle(rowA1, page, 'Column A1-A');

    // Verify initial order
    await expect(elementTitleLocators(colA1A)).toHaveText(['Block 1', 'Block 2', 'Block 3']);

    // Drag Block 1 to Block 3's position
    await performDrag(
      page,
      dragHandle(page, 'Block 1'),
      dragHandle(page, 'Block 3'),
    );

    // Block 1 should no longer be first (exact position depends on closestCenter resolution)
    await expect(
      colA1A.locator('.element-card').first().locator('.element-card__title'),
    ).not.toHaveText('Block 1');

    // All 3 blocks should still be present
    await expect(elementCards(colA1A)).toHaveCount(3);
  });

  test('move element between columns', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1 = sectionAlpha.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const colA1A = columnByTitle(rowA1, page, 'Column A1-A');
    const colA1B = columnByTitle(rowA1, page, 'Column A1-B');

    // Verify initial counts
    await expect(elementCards(colA1A)).toHaveCount(3);
    await expect(elementCards(colA1B)).toHaveCount(2);

    // Drag Block 1 from Col A1-A to Block 4 in Col A1-B
    await performDrag(
      page,
      dragHandle(page, 'Block 1'),
      dragHandle(page, 'Block 4'),
    );

    // Col A1-A loses one, Col A1-B gains one
    await expect(elementCards(colA1A)).toHaveCount(2);
    await expect(elementCards(colA1B)).toHaveCount(3);
  });

  test('reorder columns within row', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1 = sectionAlpha.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const columns = rowA1.getByTestId('column-block');

    // Verify initial order via the column-level drag handle (scoped to header
    // to avoid matching element handles nested inside the column body)
    const firstColHandle = columns.first().locator('.column-block__header [data-testid="drag-handle"]');
    await expect(firstColHandle).toHaveAttribute('aria-label', 'Move Column A1-A');

    // Drag Col A1-A past Col A1-B
    await performDrag(
      page,
      dragHandle(page, 'Column A1-A'),
      dragHandle(page, 'Column A1-B'),
    );

    // After swap, A1-B should be first
    await expect(firstColHandle).toHaveAttribute('aria-label', 'Move Column A1-B');
  });

  test('reorder rows within section', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rows = sectionAlpha.locator('.row-block');

    // Verify initial order: Alpha-1 first
    await expect(rows.first().locator('.row-block__title')).toHaveText('Row Alpha-1');

    // Drag Row Alpha-1 past Row Alpha-2
    await performDrag(
      page,
      dragHandle(page, 'Row Alpha-1'),
      dragHandle(page, 'Row Alpha-2'),
    );

    // After swap, Alpha-2 should be first
    await expect(rows.first().locator('.row-block__title')).toHaveText('Row Alpha-2');
  });

  test('move row between sections', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const sectionBeta = page.getByTestId('section-block').filter({ hasText: 'Section Beta' });

    // Verify initial counts
    await expect(sectionAlpha.locator('.row-block')).toHaveCount(2);
    await expect(sectionBeta.locator('.row-block')).toHaveCount(2);

    // Drag Row Alpha-2 to Row Beta-1
    await performDrag(
      page,
      dragHandle(page, 'Row Alpha-2'),
      dragHandle(page, 'Row Beta-1'),
    );

    // Alpha loses a row, Beta gains one
    await expect(sectionAlpha.locator('.row-block')).toHaveCount(1);
    await expect(sectionBeta.locator('.row-block')).toHaveCount(3);
  });

  test('reorder sections', async ({ page }) => {
    await setupPage(page);

    const sections = page.getByTestId('section-block');

    // Verify initial order: Alpha first
    await expect(sections.first().locator('.section-block__title')).toHaveText('Section Alpha');

    // Drag Section Beta before Section Alpha
    await performDrag(
      page,
      dragHandle(page, 'Section Beta'),
      dragHandle(page, 'Section Alpha'),
    );

    // After swap, Beta should be first
    await expect(sections.first().locator('.section-block__title')).toHaveText('Section Beta');
  });

  // --- Persistence ---

  test('reorder survives page reload', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1 = sectionAlpha.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const colA1A = columnByTitle(rowA1, page, 'Column A1-A');

    await expect(elementTitleLocators(colA1A)).toHaveText(['Block 1', 'Block 2', 'Block 3']);

    // Drag Block 1 to Block 3's position
    await performDrag(
      page,
      dragHandle(page, 'Block 1'),
      dragHandle(page, 'Block 3'),
    );

    // Wait for Block 1 to move away from first position (optimistic update)
    await expect(
      colA1A.locator('.element-card').first().locator('.element-card__title'),
    ).not.toHaveText('Block 1');

    // Capture the new order for comparison after reload
    const titles = elementTitleLocators(colA1A);
    const afterDragTitles = await titles.allTextContents();

    // Hard reload
    await page.reload();
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Locate elements again after reload
    const sectionAlphaReload = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1Reload = sectionAlphaReload.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const colA1AReload = columnByTitle(rowA1Reload, page, 'Column A1-A');

    await expect(elementTitleLocators(colA1AReload)).toHaveText(afterDragTitles);
  });

  // --- Error handling ---

  test('API failure triggers rollback to original order', async ({ page }) => {
    await setupPage(page);

    const sectionAlpha = page.getByTestId('section-block').filter({ hasText: 'Section Alpha' });
    const rowA1 = sectionAlpha.locator('.row-block').filter({ hasText: 'Row Alpha-1' });
    const colA1A = columnByTitle(rowA1, page, 'Column A1-A');

    // Verify initial order
    await expect(elementTitleLocators(colA1A)).toHaveText(['Block 1', 'Block 2', 'Block 3']);

    // Intercept the reorder API and return 500
    await page.route('**/api/reorder', (route) =>
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ error: 'Internal Server Error' }),
      }),
    );

    // Perform the drag
    await performDrag(
      page,
      dragHandle(page, 'Block 1'),
      dragHandle(page, 'Block 3'),
    );

    // After error + rollback + refetch, original order should be restored.
    // Playwright's auto-retry handles the async settle.
    await expect(elementTitleLocators(colA1A)).toHaveText(
      ['Block 1', 'Block 2', 'Block 3'],
      { timeout: 10_000 },
    );

    // Clean up the route intercept
    await page.unroute('**/api/reorder');
  });
});
