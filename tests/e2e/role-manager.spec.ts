import { test, expect } from '@playwright/test';

test.describe('Role: Manager — all accessible pages', () => {
  test.use({ storageState: 'tests/e2e/.auth/manager.json' });

  const pages = [
    '/dashboard',
    '/attendance/clock-in',
    '/attendance',
    '/leaves',
    '/leaves/apply',
    '/overtimes',
    '/overtimes/apply',
    '/reimbursements',
    '/reimbursements/apply',
    '/loans',
    '/assets',
    '/payroll',
    '/knowledge-base',
    '/approvals',
    '/settings/profile',
    '/settings/security',
    '/settings/appearance',
  ];

  for (const route of pages) {
    test(`${route} loads successfully`, async ({ page }) => {
      const resp = await page.goto(route);
      expect(resp?.status()).toBeLessThan(500);
    });
  }
});
