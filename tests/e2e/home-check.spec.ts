import { test, expect } from '@playwright/test';

test.describe('Home page visual check', () => {
  test.use({ storageState: 'tests/e2e/.auth/employee.json' });

  test('home page screenshot', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', msg => {
      if (msg.type() === 'error') errors.push(msg.text());
    });

    await page.goto('/home', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    // Check for elements
    const greeting = await page.getByText(/Selamat|Good/).isVisible();
    console.log('Greeting visible:', greeting);

    const attendance = await page.getByText('Presensi').isVisible();
    console.log('Attendance panel visible:', attendance);

    const live = await page.getByText('Live').isVisible();
    console.log('Live badge visible:', live);

    const priorities = await page.getByText(/prioritas|priorities/).isVisible();
    console.log('Priority message visible:', priorities);

    console.log('Console errors:', errors.length > 0 ? errors : 'none');

    await page.screenshot({ path: '/tmp/home-page-full.png', fullPage: true });
    console.log('Screenshot saved to /tmp/home-page-full.png');
  });
});
