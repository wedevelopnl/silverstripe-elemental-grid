import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

test.describe('Collapsible containers', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('editor collapses containers to focus on content, state survives reload', async ({ page }) => {
    // Fixture: Section A (Row A1 [Col A1-L, Col A1-R], Row A2 [Col A2]), Section B (Row B1 [Col B1])
    const fixture = await loadFixture(page.request, 'collapse-test');

    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Locate sections by their heading text
    const sectionA = page.getByTestId('section-block').filter({ hasText: 'Section A' });
    const sectionB = page.getByTestId('section-block').filter({ hasText: 'Section B' });
    const sectionAToggle = sectionA.getByTestId('collapse-toggle').first();

    // Locate rows within Section A by heading text
    const rowA1 = sectionA.locator('.row-block').filter({ hasText: 'Row A1' });
    const rowA2 = sectionA.locator('.row-block').filter({ hasText: 'Row A2' });
    const rowA1Toggle = rowA1.getByTestId('collapse-toggle').first();
    const rowA2Toggle = rowA2.getByTestId('collapse-toggle').first();

    // Locate columns within Row A1
    const colA1L = rowA1.getByTestId('column-block').filter({ hasText: 'Block A1-Left' });
    const colA1R = rowA1.getByTestId('column-block').filter({ hasText: 'Block A1-Right' });
    const colA1LToggle = colA1L.getByTestId('collapse-toggle');

    // --- Everything starts expanded ---
    await expect(sectionAToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(sectionA.getByRole('heading', { name: 'Row A1' })).toBeVisible();
    await expect(sectionA.getByRole('heading', { name: 'Row A2' })).toBeVisible();
    await expect(sectionB.getByText('Block B1')).toBeVisible();
    await expect(colA1L.getByText('Block A1-Left')).toBeVisible();
    await expect(colA1R.getByText('Block A1-Right')).toBeVisible();

    // --- Collapse Section A — Section B stays expanded ---
    const urlBefore = page.url();
    await sectionAToggle.click();

    await expect(sectionAToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(sectionA.getByRole('heading', { name: 'Row A1' })).toBeHidden();
    await expect(sectionA.getByRole('heading', { name: 'Row A2' })).toBeHidden();
    await expect(sectionB.getByText('Block B1')).toBeVisible();
    // Toggle click did not navigate away
    expect(page.url()).toBe(urlBefore);

    // --- Expand Section A back ---
    await sectionAToggle.click();
    await expect(sectionAToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(sectionA.getByRole('heading', { name: 'Row A1' })).toBeVisible();

    // --- Collapse Row A1 — sibling Row A2 stays expanded ---
    await rowA1Toggle.click();
    await expect(rowA1Toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(colA1L.getByText('Block A1-Left')).toBeHidden();
    await expect(rowA2Toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(rowA2.getByText('Block A2')).toBeVisible();

    // --- Expand Row A1 to access columns ---
    await rowA1Toggle.click();
    await expect(rowA1Toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(colA1L.getByText('Block A1-Left')).toBeVisible();

    // --- Collapse Column A1-Left — sibling Column A1-Right stays expanded ---
    await colA1LToggle.click();
    await expect(colA1LToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(colA1L.getByText('Block A1-Left')).toBeHidden();
    await expect(colA1R.getByText('Block A1-Right')).toBeVisible();

    // --- Build up nested collapsed state for persistence test ---
    // Collapse Row A2 (Row A1 stays expanded with collapsed column inside)
    await rowA2Toggle.click();
    await expect(rowA2Toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(rowA2.getByText('Block A2')).toBeHidden();
    await expect(rowA1Toggle).toHaveAttribute('aria-expanded', 'true');

    // Collapse parent Section A to test child state independence
    await sectionAToggle.click();
    await expect(sectionAToggle).toHaveAttribute('aria-expanded', 'false');

    // --- Expand Section A — child states preserved ---
    await sectionAToggle.click();
    await expect(sectionAToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(rowA1Toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(rowA2Toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(colA1LToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(colA1L.getByText('Block A1-Left')).toBeHidden();
    await expect(colA1R.getByText('Block A1-Right')).toBeVisible();

    // --- Collapse Section A again for the reload test ---
    await sectionAToggle.click();
    await expect(sectionAToggle).toHaveAttribute('aria-expanded', 'false');

    // --- Reload page — all collapsed state persists ---
    await page.reload();
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Re-locate toggles after reload
    const sectionAReload = page.getByTestId('section-block').filter({ hasText: 'Section A' });
    const sectionBReload = page.getByTestId('section-block').filter({ hasText: 'Section B' });
    const sectionAToggleReload = sectionAReload.getByTestId('collapse-toggle').first();
    const sectionBToggleReload = sectionBReload.getByTestId('collapse-toggle').first();

    // Section A collapsed, Section B still expanded
    await expect(sectionAToggleReload).toHaveAttribute('aria-expanded', 'false');
    await expect(sectionBToggleReload).toHaveAttribute('aria-expanded', 'true');

    // Expand Section A to verify nested states survived the reload
    await sectionAToggleReload.click();
    await expect(sectionAToggleReload).toHaveAttribute('aria-expanded', 'true');

    const rowA1Reload = sectionAReload.locator('.row-block').filter({ hasText: 'Row A1' });
    const rowA2Reload = sectionAReload.locator('.row-block').filter({ hasText: 'Row A2' });
    const colA1LReload = rowA1Reload.getByTestId('column-block').filter({ hasText: 'Block A1-Left' });
    const colA1RReload = rowA1Reload.getByTestId('column-block').filter({ hasText: 'Block A1-Right' });

    await expect(rowA1Reload.getByTestId('collapse-toggle').first()).toHaveAttribute('aria-expanded', 'true');
    await expect(rowA2Reload.getByTestId('collapse-toggle').first()).toHaveAttribute('aria-expanded', 'false');
    await expect(colA1LReload.getByTestId('collapse-toggle')).toHaveAttribute('aria-expanded', 'false');
    await expect(colA1LReload.getByText('Block A1-Left')).toBeHidden();
    await expect(colA1RReload.getByText('Block A1-Right')).toBeVisible();

    // --- Round-trip: expand everything back to starting state ---
    await colA1LReload.getByTestId('collapse-toggle').click();
    await rowA2Reload.getByTestId('collapse-toggle').first().click();
    await expect(colA1LReload.getByText('Block A1-Left')).toBeVisible();
    await expect(rowA2Reload.getByText('Block A2')).toBeVisible();
  });
});
