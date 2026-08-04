import { expect, test } from '@playwright/test';

const PAGES = [
  '/home',
  '/notifications',
  '/attendance-history',
  '/apply-leave',
  '/attendance-corrections',
  '/reimbursement',
  '/my-schedule',
  '/shift-swap-requests',
  '/wfh-requests',
  '/document-requests',
  '/hr-tasks',
  '/my-tasks',
  '/collaboration',
  '/forms',
  '/approvals',
  '/approvals/history',
  '/overtime',
  '/my-kasbon',
  '/team-kasbon',
  '/face-enrollment',
  '/my-assets',
  '/my-performance',
  '/payroll',
  '/knowledge-base/chat',
] as const;

async function expectHealthyPage(page, path: string) {
  const resp = await page.goto(path, { waitUntil: 'networkidle' });
  // 403/redirect is fine — what matters is no 500 crash
  expect(resp!.status()).toBeLessThan(500);
  await expect(page.locator('body')).toBeVisible();
  await expect(page.locator('body')).not.toContainText(/exception|stack trace|server error|whoops/i);
}

for (const pagePath of PAGES) {
  const label = pagePath.replace(/^\//, '').replace(/[/-]/g, '_');
  test(`${pagePath} renders without server error`, async ({ page }) => {
    await expectHealthyPage(page, pagePath);
  });
}
