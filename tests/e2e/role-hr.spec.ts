import { test, expect } from '@playwright/test';

test.describe('Role: HR-Manager — all accessible pages', () => {
  const pages = [
    '/dashboard',
    '/admin/employees',
    '/admin/employees/create',
    '/attendance/clock-in',
    '/leaves',
    '/overtimes',
    '/reimbursements',
    '/loans',
    '/assets',
    '/payroll',
  ];

  for (const route of pages) {
    test(`${route} loads successfully`, async ({ page }) => {
      const resp = await page.goto(route);
      expect(resp?.status()).toBeLessThan(400);
    });
  }
});
