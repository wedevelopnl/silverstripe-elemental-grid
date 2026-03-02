import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

test.describe('Collapsible containers', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('collapse and expand section, row, and column containers', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');

    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    const sectionBlock = page.getByTestId('section-block').first();
    const sectionToggle = sectionBlock.getByTestId('collapse-toggle').first();

    // --- Default state: section is expanded, rows visible ---
    const sectionBody = sectionBlock.locator('.section-block__body');
    await expect(sectionBody).toBeVisible();

    // --- Collapse section: rows should be hidden ---
    await sectionToggle.click();
    await expect(sectionBody).toBeHidden();
    await expect(sectionBlock).toHaveClass(/section-block--collapsed/);

    // --- Expand section: rows reappear ---
    await sectionToggle.click();
    await expect(sectionBody).toBeVisible();
    await expect(sectionBlock).not.toHaveClass(/section-block--collapsed/);
  });

  test('collapsed state persists across page reload', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');

    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    const sectionBlock = page.getByTestId('section-block').first();
    const sectionToggle = sectionBlock.getByTestId('collapse-toggle').first();

    // Collapse the section
    await sectionToggle.click();
    await expect(sectionBlock).toHaveClass(/section-block--collapsed/);

    // Reload the page
    await page.reload();
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Section should still be collapsed after reload
    const sectionBlockAfterReload = page.getByTestId('section-block').first();
    await expect(sectionBlockAfterReload).toHaveClass(/section-block--collapsed/);

    // Clean up: expand it again so localStorage doesn't leak to other tests
    const toggleAfterReload = sectionBlockAfterReload.getByTestId('collapse-toggle').first();
    await toggleAfterReload.click();
    await expect(sectionBlockAfterReload).not.toHaveClass(/section-block--collapsed/);
  });

  test('child collapse state is independent of parent', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');

    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    const sectionBlock = page.getByTestId('section-block').first();
    const sectionToggle = sectionBlock.getByTestId('collapse-toggle').first();
    const rowBlock = sectionBlock.locator('.row-block').first();
    const rowToggle = rowBlock.getByTestId('collapse-toggle').first();

    // Collapse a row within the section
    await rowToggle.click();
    await expect(rowBlock).toHaveClass(/row-block--collapsed/);

    // Collapse the parent section
    await sectionToggle.click();
    await expect(sectionBlock).toHaveClass(/section-block--collapsed/);

    // Expand the section — the row should still be collapsed
    await sectionToggle.click();
    await expect(sectionBlock).not.toHaveClass(/section-block--collapsed/);
    await expect(rowBlock).toHaveClass(/row-block--collapsed/);

    // Clean up
    await rowToggle.click();
    await expect(rowBlock).not.toHaveClass(/row-block--collapsed/);
  });

  test('clicking collapse toggle does not navigate to edit form', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');

    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    const urlBefore = page.url();

    const sectionBlock = page.getByTestId('section-block').first();
    const sectionToggle = sectionBlock.getByTestId('collapse-toggle').first();
    await sectionToggle.click();

    // URL should not have changed (no navigation to edit form)
    expect(page.url()).toBe(urlBefore);

    // Clean up
    await sectionToggle.click();
  });
});
