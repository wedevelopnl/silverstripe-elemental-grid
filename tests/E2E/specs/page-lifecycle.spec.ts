import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

test.describe('Page lifecycle', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('editor can open page, see grid editor, and publish to frontend', async ({
    page,
  }) => {
    // Use page.request to share browser context cookies and user agent,
    // preventing SilverStripe's strict_user_agent_check from invalidating the session
    const fixture = await loadFixture(page.request, 'empty-page');

    // Navigate to CMS page editor
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);

    // Assert the page title is visible in the CMS editor
    await expect(
      page.locator('#Form_EditForm_Title'),
    ).toHaveValue('E2E Empty Page');

    // Assert the elemental grid editor component is present
    await expect(
      page.locator('[data-elemental-grid]'),
    ).toBeVisible();

    // Publish the page
    await page
      .getByRole('button', { name: /Publish/ })
      .click();

    // Wait for publish confirmation
    await expect(
      page.locator('.toast-notification, .message--success, .notice--success'),
    ).toBeVisible({ timeout: 10_000 });

    // Navigate to the published frontend URL
    await page.goto(fixture.pageUrl);

    // Assert the page title renders on the frontend
    await expect(page.locator('h1')).toContainText('E2E Empty Page');
  });
});
