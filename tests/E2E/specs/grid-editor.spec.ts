import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

/**
 * Wait for the grid editor to finish loading. The loading indicator is rendered
 * while the element tree is being fetched from the API; once it disappears the
 * full component hierarchy is mounted and interactive.
 */
async function waitForGridEditor(page: import('@playwright/test').Page): Promise<void> {
  await expect(page.locator('.grid-editor__loading')).toBeHidden({ timeout: 15_000 });
}

// ---------------------------------------------------------------------------
// 1. Grid layout rendering
// ---------------------------------------------------------------------------
test.describe('Grid layout rendering', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('editor sees sections, rows, columns, and element cards', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    // Section
    await expect(page.locator('.section-block')).toHaveCount(1);
    await expect(
      page.locator('.section-block', { hasText: 'Main Section' }),
    ).toBeVisible();

    // Row
    await expect(page.locator('.row-block')).toHaveCount(1);
    await expect(
      page.locator('.row-block', { hasText: 'First Row' }),
    ).toBeVisible();

    // Columns
    await expect(page.locator('.column-block')).toHaveCount(2);

    // Element cards — 3 leaf elements across both columns
    await expect(page.locator('.element-card')).toHaveCount(3);
    await expect(
      page.locator('.element-card', { hasText: 'Text Block' }),
    ).toBeVisible();
    await expect(
      page.locator('.element-card', { hasText: 'Image Block' }),
    ).toBeVisible();
    await expect(
      page.locator('.element-card', { hasText: 'Video Block' }),
    ).toBeVisible();
  });

  test('columns display fraction badges matching their grid settings', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    // Default viewport is "md": col1=8/12, col2=4/12
    const badges = page.locator('.column-block__badge');
    await expect(badges).toHaveCount(2);
    await expect(badges.nth(0)).toHaveText('8/12');
    await expect(badges.nth(1)).toHaveText('4/12');
  });
});

// ---------------------------------------------------------------------------
// 2. Viewport switcher
// ---------------------------------------------------------------------------
test.describe('Viewport switcher', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('renders a tab for each adapter viewport with default selected', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    const buttons = page.locator('.viewport-switcher__button');
    await expect(buttons).toHaveCount(6);

    // Verify all viewport labels are present
    const expectedLabels = [
      'Extra Small',
      'Small',
      'Medium',
      'Large',
      'Extra Large',
      'Extra Extra Large',
    ];
    for (const label of expectedLabels) {
      await expect(buttons.filter({ hasText: label }).first()).toBeVisible();
    }

    // "Medium" (md) is the default — should be active
    const mediumButton = buttons.filter({ hasText: 'Medium' }).first();
    await expect(mediumButton).toHaveClass(/viewport-switcher__button--active/);
    await expect(mediumButton).toHaveAttribute('aria-pressed', 'true');
  });

  test('switching viewport updates fraction badges', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    const badges = page.locator('.column-block__badge');

    // Default md: 8/12, 4/12
    await expect(badges.nth(0)).toHaveText('8/12');
    await expect(badges.nth(1)).toHaveText('4/12');

    // Switch to "Large" (lg): both columns become 6/12
    await page.locator('.viewport-switcher__button', { hasText: 'Large' }).click();
    await expect(badges.nth(0)).toHaveText('6/12');
    await expect(badges.nth(1)).toHaveText('6/12');
  });

  test('switching to viewport where column is hidden shows hidden indicator', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    // Switch to "Extra Small" (xs): col2 has visible=false
    await page
      .locator('.viewport-switcher__button', { hasText: 'Extra Small' })
      .click();

    // Second column should get the hidden modifier class
    const columns = page.locator('.column-block');
    await expect(columns.nth(1)).toHaveClass(/column-block--hidden/);

    // Its badge should display "hidden" instead of a fraction
    const badges = page.locator('.column-block__badge');
    await expect(badges.nth(1)).toHaveText('hidden');

    // First column remains visible with 12/12
    await expect(columns.nth(0)).not.toHaveClass(/column-block--hidden/);
    await expect(badges.nth(0)).toHaveText('12/12');
  });
});

// ---------------------------------------------------------------------------
// 3. Publication state
// ---------------------------------------------------------------------------
test.describe('Publication state', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('element cards show correct status modifier per publication state', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'complex-page');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    // Draft-only element (unpublished after page publishRecursive)
    const draftCard = page.locator('.element-card', { hasText: 'Draft Only Block' }).first();
    await expect(draftCard).toHaveClass(/element-card--draft/);

    // Published element (on both Draft and Live)
    const publishedCard = page.locator('.element-card', { hasText: 'Published Block' }).first();
    await expect(publishedCard).toHaveClass(/element-card--published/);

    // Modified element (published, then draft title changed)
    const modifiedCard = page
      .locator('.element-card', { hasText: /Modified Text Block/ })
      .first();
    await expect(modifiedCard).toHaveClass(/element-card--modified/);
  });

  test('status borders appear at section, row, column, and element levels', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'complex-page');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    // Each container level should carry a status modifier class.
    // The exact status depends on the versioned state of each record;
    // we verify at least one element at each level has a status class.
    await expect(
      page.locator('[class*="section-block--"]').first(),
    ).toBeVisible();
    await expect(
      page.locator('[class*="row-block--"]').first(),
    ).toBeVisible();
    await expect(
      page.locator('[class*="column-block--"]').first(),
    ).toBeVisible();
    await expect(
      page.locator('[class*="element-card--"]').first(),
    ).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// 4. Empty states
// ---------------------------------------------------------------------------
test.describe('Empty states', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('empty page shows "No sections yet" message', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'empty-page');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await waitForGridEditor(page);

    await expect(
      page.locator('.empty-state', { hasText: 'No sections yet' }),
    ).toBeVisible();

    // No sections, rows, columns, or element cards should be present
    await expect(page.locator('.section-block')).toHaveCount(0);
    await expect(page.locator('.row-block')).toHaveCount(0);
    await expect(page.locator('.column-block')).toHaveCount(0);
    await expect(page.locator('.element-card')).toHaveCount(0);
  });
});
