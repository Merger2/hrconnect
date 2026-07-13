import { test, expect } from '@playwright/test';

test.describe('Role: Finance — all accessible pages', () => {
  const pages = [
    '/dashboard',
    '/loans',
    '/payroll',
    '/admin/payroll',
    '/admin/reimbursements',
    '/approvals',
  ];

  for (const route of pages) {
    test(`${route} loads successfully`, async ({ page }) => {
      const resp = await page.goto(route);
      expect(resp?.status()).toBeLessThan(400);
    });
  }
});
