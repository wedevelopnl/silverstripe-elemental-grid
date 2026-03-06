import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';
import { performDrag } from '../helpers/drag';

/** Get a drag handle by its aria-label (e.g. "Move Main-Alpha"). */
function dragHandle(page: import('@playwright/test').Page, name: string) {
  return page.locator(`[data-testid="drag-handle"][aria-label="Move ${name}"]`);
}

/**
 * Register response listeners for the reorder mutation lifecycle.
 * Must be called BEFORE the action that triggers the mutation (drag release).
 */
function waitForMutationSettlement(page: import('@playwright/test').Page) {
  const reorderDone = page.waitForResponse(
    (resp) => resp.url().includes('/api/reorder') && resp.ok(),
  );
  const refetchDone = page.waitForResponse(
    (resp) => resp.url().includes('/api/readTree/') && resp.ok(),
  );

  return async () => {
    await reorderDone;
    await refetchDone;
    await page.waitForTimeout(500);
  };
}

test.describe('Multi-zone isolation', () => {
  // Two zones stacked vertically need a tall viewport
  test.use({ viewport: { width: 1280, height: 1400 } });

  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('zones render independently, reorder within zones, and reject cross-zone drags', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'multi-zone');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);

    // Wait for both grid editors to finish loading
    const gridEditors = page.getByTestId('grid-editor');
    await expect(page.getByTestId('grid-editor-loading')).toHaveCount(0, { timeout: 15_000 });
    await expect(gridEditors).toHaveCount(2);

    // Identify zones by data-zone attribute
    const mainZone = page.locator('[data-testid="grid-editor"][data-zone="main"]');
    const sidebarZone = page.locator('[data-testid="grid-editor"][data-zone="sidebar"]');

    // --- Phase 1: Verify both zones render independently ---
    const mainSections = mainZone.getByTestId('section-block');
    const sidebarSections = sidebarZone.getByTestId('section-block');

    await expect(mainSections).toHaveCount(2);
    await expect(sidebarSections).toHaveCount(2);

    await expect(mainSections.getByTestId('section-title')).toHaveText(['Main-Alpha', 'Main-Beta']);
    await expect(sidebarSections.getByTestId('section-title')).toHaveText(['Sidebar-Alpha', 'Sidebar-Beta']);

    // --- Phase 2: Reorder within main zone ---
    const settle1 = waitForMutationSettlement(page);
    await performDrag(page, dragHandle(page, 'Main-Alpha'), dragHandle(page, 'Main-Beta'));
    await settle1();

    // Main-Beta should now be first
    await expect(mainSections.getByTestId('section-title')).toHaveText(['Main-Beta', 'Main-Alpha']);
    // Sidebar unchanged
    await expect(sidebarSections.getByTestId('section-title')).toHaveText(['Sidebar-Alpha', 'Sidebar-Beta']);

    // --- Phase 3: Reorder within sidebar zone ---
    const settle2 = waitForMutationSettlement(page);
    await performDrag(page, dragHandle(page, 'Sidebar-Alpha'), dragHandle(page, 'Sidebar-Beta'));
    await settle2();

    await expect(sidebarSections.getByTestId('section-title')).toHaveText(['Sidebar-Beta', 'Sidebar-Alpha']);
    // Main unchanged
    await expect(mainSections.getByTestId('section-title')).toHaveText(['Main-Beta', 'Main-Alpha']);

    // --- Phase 4: Cross-zone drag cannot move sections between zones ---
    // dnd-kit resolves to the nearest same-zone collision (not cross-zone),
    // so a reorder may fire within the main zone. The key invariant:
    // no section moves between zones — counts stay the same.
    await performDrag(page, dragHandle(page, 'Main-Beta'), dragHandle(page, 'Sidebar-Beta'));
    await page.waitForTimeout(500);

    // Both zones still have exactly 2 sections each
    await expect(mainSections).toHaveCount(2);
    await expect(sidebarSections).toHaveCount(2);
    // Sidebar order is unchanged (never affected by main-zone drag)
    await expect(sidebarSections.getByTestId('section-title')).toHaveText(['Sidebar-Beta', 'Sidebar-Alpha']);

    // --- Phase 5: Publish and verify all sections render on frontend ---
    await page.getByRole('button', { name: /Publish/ }).click();
    await expect(
      page.getByRole('button', { name: /Published/ }),
    ).toBeVisible({ timeout: 10_000 });

    const livePath = fixture.pageUrl.split('?')[0];
    await page.goto(livePath);

    // All 4 section headings should be present on the frontend.
    // Order depends on Sort + zone interleaving (not grouped by zone),
    // so we just verify all titles appear.
    const frontendHeadings = page.getByRole('heading', { level: 2 });
    await expect(frontendHeadings).toHaveCount(4);
    const headingTexts = await frontendHeadings.allTextContents();
    expect(headingTexts).toContain('Main-Alpha');
    expect(headingTexts).toContain('Main-Beta');
    expect(headingTexts).toContain('Sidebar-Alpha');
    expect(headingTexts).toContain('Sidebar-Beta');
  });
});
