import { test, expect } from '@playwright/test';

test.describe('Role: HR-Manager — all accessible pages', () => {
  test.use({ storageState: 'tests/e2e/.auth/hr.json' });

  const pages = [
    '/dashboard',
    '/attendance/clock-in',
    '/attendance/face-registration',
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
    // HR Admin
    '/admin/employees',
    '/admin/employees/create',
    '/admin/leaves',
    '/admin/overtimes',
    '/admin/payroll',
    '/admin/reimbursements',
    // Approvals
    '/approvals',
    // Master Data
    '/master-data/branches',
    '/master-data/departments',
    '/master-data/positions',
    '/master-data/shifts',
    '/master-data/holidays',
    '/master-data/leave-types',
    // Settings
    '/settings/profile',
    '/settings/security',
  ];

  for (const route of pages) {
    test(`${route} loads successfully`, async ({ page }) => {
      const resp = await page.goto(route);
      expect(resp?.status()).toBeLessThan(400);
    });
  }
});
