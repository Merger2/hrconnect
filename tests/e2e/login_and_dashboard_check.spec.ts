import { test, expect } from '@playwright/test';

test('login and check dashboard', async ({ page }) => {
  await page.goto('http://localhost:8000/login');

  await page.fill('input[name="email"]', 'fikihaldiansyah28@gmail.com');
  await page.fill('input[name="password"]', 'ChangeMe!2026');
  await page.click('button[type="submit"]');

  await page.waitForURL('**/admin/dashboard');

  const heading = await page.textContent('h1');
  expect(heading).toContain('Dashboard');
});
