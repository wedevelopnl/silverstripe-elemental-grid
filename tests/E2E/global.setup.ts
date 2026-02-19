import { expect, test as setup } from '@playwright/test';

const AUTH_FILE = 'tests/E2E/.auth/admin.json';

setup('authenticate as admin', async ({ page }) => {
  await page.goto('/admin/');

  await page.getByLabel('Email').fill('admin');
  await page.getByLabel('Password').fill('admin');
  await page.getByRole('button', { name: 'Log in' }).click();

  // Wait for redirect to CMS after successful login
  await expect(page).toHaveURL(/\/admin\//);

  await page.context().storageState({ path: AUTH_FILE });
});
