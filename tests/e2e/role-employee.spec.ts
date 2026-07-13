import { test, expect } from '@playwright/test';

test.describe('Role: Employee — all accessible pages', () => {
  const pages = [
    '/dashboard',
    '/attendance/clock-in',
    '/leaves',
    '/overtimes',
    '/reimbursements',
    '/loans',
    '/assets',
    '/payroll',
    '/knowledge-base',
  ];

  for (const route of pages) {
    test(`${route} loads successfully`, async ({ page }) => {
      const resp = await page.goto(route);
      expect(resp?.status()).toBeLessThan(400);
    });
  }
});
