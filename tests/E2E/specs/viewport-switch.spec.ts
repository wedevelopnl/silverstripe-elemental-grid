import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

test.describe('Viewport switcher', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('switching viewports updates column widths and visibility', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');

    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.getByTestId('grid-editor-loading'),
    ).toBeHidden({ timeout: 15_000 });

    const viewportGroup = page.getByRole('group', { name: 'Viewport size' });
    const viewportButtons = page.getByTestId('viewport-button');
    const leftColumn = page.getByTestId('column-block').first();
    const rightColumn = page.getByTestId('column-block').nth(1);
    const leftBadge = leftColumn.getByTestId('column-badge');
    const rightBadge = rightColumn.getByTestId('column-badge');

    // --- Initial state: md (default) ---
    await expect(viewportGroup).toBeVisible();
    await expect(viewportButtons).toHaveCount(6);

    const mediumButton = page.getByRole('button', { name: 'Medium', exact: true });
    await expect(mediumButton).toHaveAttribute('aria-pressed', 'true');

    // All other viewport buttons should be inactive
    for (const label of ['Extra Small', 'Small', 'Large', 'Extra Large', 'Extra Extra Large']) {
      await expect(
        page.getByRole('button', { name: label, exact: true }),
      ).toHaveAttribute('aria-pressed', 'false');
    }

    await expect(leftBadge).toHaveText('8/12');
    await expect(rightBadge).toHaveText('4/12');
    await expect(leftColumn).not.toHaveCSS('opacity', '0.4');
    await expect(rightColumn).not.toHaveCSS('opacity', '0.4');

    // --- Switch to xs: left full-width, right hidden ---
    const xsButton = page.getByRole('button', { name: 'Extra Small', exact: true });
    await xsButton.click();

    await expect(xsButton).toHaveAttribute('aria-pressed', 'true');
    await expect(mediumButton).toHaveAttribute('aria-pressed', 'false');

    await expect(leftBadge).toHaveText('12/12');
    await expect(leftColumn).not.toHaveCSS('opacity', '0.4');
    await expect(rightBadge).toHaveText('hidden');
    await expect(rightColumn).toHaveCSS('opacity', '0.4');

    // --- Switch to lg: equal-width columns ---
    const lgButton = page.getByRole('button', { name: 'Large', exact: true });
    await lgButton.click();

    await expect(lgButton).toHaveAttribute('aria-pressed', 'true');
    await expect(xsButton).toHaveAttribute('aria-pressed', 'false');

    await expect(leftBadge).toHaveText('6/12');
    await expect(rightBadge).toHaveText('6/12');
    await expect(leftColumn).not.toHaveCSS('opacity', '0.4');
    await expect(rightColumn).not.toHaveCSS('opacity', '0.4');

    // --- Round-trip back to md: state restores ---
    await mediumButton.click();

    await expect(mediumButton).toHaveAttribute('aria-pressed', 'true');
    await expect(lgButton).toHaveAttribute('aria-pressed', 'false');

    await expect(leftBadge).toHaveText('8/12');
    await expect(rightBadge).toHaveText('4/12');
    await expect(leftColumn).not.toHaveCSS('opacity', '0.4');
    await expect(rightColumn).not.toHaveCSS('opacity', '0.4');
  });
});
