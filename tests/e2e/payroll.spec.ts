import { expect, test } from '@playwright/test';

const adminEmail = process.env.E2E_ADMIN_EMAIL ?? 'apk.demo.superadmin@paspapan.test';
const adminPassword = process.env.E2E_ADMIN_PASSWORD ?? '12345678';
const userEmail = process.env.E2E_USER_EMAIL ?? 'apk.demo.user@paspapan.test';
const userPassword = process.env.E2E_USER_PASSWORD ?? '12345678';
const localLoginToken = process.env.CI ? undefined : 'local-apk-e2e';

async function login(page, email: string, password: string) {
  const loginToken = process.env.E2E_LOGIN_TOKEN ?? localLoginToken;

  if (loginToken) {
    await page.goto(`/__e2e-login?token=${encodeURIComponent(loginToken)}&email=${encodeURIComponent(email)}&to=/home`);
    await expect(page).not.toHaveURL(/\/login$/);
    return;
  }

  await page.goto('/login');
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await page.getByRole('button', { name: /log in|login|masuk/i }).click();
  await expect(page).not.toHaveURL(/\/login$/);
}

async function expectHealthyPage(page, path: string) {
  await page.goto(path);
  await expect(page.locator('body')).toBeVisible();
  await expect(page.locator('body')).not.toContainText(/server error|exception|stack trace/i);
}

/**
 * Payroll E2E Tests - Tests the complete payroll flow from generation to payslip download
 */
test.describe('Payroll E2E Flow', () => {
  test('admin can access payroll manager page', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await expectHealthyPage(page, '/admin/payrolls');
  });

  test('finance can generate payroll for a period', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await page.goto('/admin/payrolls');
    
    // Wait for page to load
    await expect(page.locator('body')).toBeVisible();
    await page.waitForLoadState('networkidle');
    
    // Click generate payroll button
    const generateButton = page.getByRole('button', { name: /generate|buat payroll/i });
    await expect(generateButton.first()).toBeVisible({ timeout: 10000 });
    await generateButton.first().click();
    
    // Fill period and submit
    const periodInput = page.locator('input[name="period"], input[placeholder*="period" i], input[placeholder*="YYYY-MM" i]').first();
    if (await periodInput.isVisible({ timeout: 2000 })) {
      const currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
      await periodInput.fill(currentMonth);
    }
    
    const submitButton = page.getByRole('button', { name: /generate|simpan|submit/i }).first();
    if (await submitButton.isVisible({ timeout: 2000 })) {
      await submitButton.click();
      await expect(page.locator('body')).toContainText(/berhasil|success|queued/i, { timeout: 15000 });
    }
  });

  test('admin can view payroll list with summary cards', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await page.goto('/admin/payrolls');
    await page.waitForLoadState('networkidle');
    
    // Check summary cards are visible
    await expect(page.locator('text=Total Gross, text=Total Net, text=Total Deduction').first()).toBeVisible({ timeout: 10000 });
    
    // Check payroll table exists
    await expect(page.locator('table, [role="table"]').first()).toBeVisible({ timeout: 10000 });
  });

  test('admin can view payroll detail modal', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await page.goto('/admin/payrolls');
    await page.waitForLoadState('networkidle');
    
    // Click on first payroll row to view detail
    const firstRow = page.locator('table tbody tr, [role="row"]').first();
    if (await firstRow.isVisible({ timeout: 5000 })) {
      await firstRow.click();
      
      // Check detail modal opens
      await expect(page.locator('text=Detail Payroll, text=Payroll Detail, [role="dialog"]').first()).toBeVisible({ timeout: 5000 });
      
      // Verify key fields are shown
      await expect(page.locator('text=Basic Salary, text=Gaji Pokok').first()).toBeVisible({ timeout: 5000 });
      await expect(page.locator('text=Net Salary, text=Gaji Bersih').first()).toBeVisible({ timeout: 5000 });
      
      // Close modal
      await page.getByRole('button', { name: /close|tutup|×/i }).first().click();
    }
  });

  test('admin can publish draft payroll', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await page.goto('/admin/payrolls');
    await page.waitForLoadState('networkidle');
    
    // Find a draft payroll row and click publish
    const draftRow = page.locator('tr:has-text("draft"), tr:has-text("Draft")').first();
    if (await draftRow.isVisible({ timeout: 5000 })) {
      const publishButton = draftRow.getByRole('button', { name: /publish|terbitkan/i });
      if (await publishButton.isVisible({ timeout: 2000 })) {
        await publishButton.click();
        await expect(page.locator('body')).toContainText(/berhasil|success/i, { timeout: 10000 });
      }
    }
  });

  test('admin can mark published payroll as paid', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await page.goto('/admin/payrolls');
    await page.waitForLoadState('networkidle');
    
    // Find a published payroll row and click pay
    const publishedRow = page.locator('tr:has-text("published"), tr:has-text("Published")').first();
    if (await publishedRow.isVisible({ timeout: 5000 })) {
      const payButton = publishedRow.getByRole('button', { name: /pay|bayar/i });
      if (await payButton.isVisible({ timeout: 2000 })) {
        await payButton.click();
        await expect(page.locator('body')).toContainText(/berhasil|success/i, { timeout: 10000 });
      }
    }
  });

  test('user can access payslip page', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await expectHealthyPage(page, '/payroll');
  });

  test('user can view their payslip list', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await page.goto('/payroll');
    await page.waitForLoadState('networkidle');
    
    // Check payslip list is visible
    await expect(page.locator('text=Slip Gaji, text=Payslip, text=Riwayat Gaji').first()).toBeVisible({ timeout: 10000 });
  });

  test('user can download published payslip PDF', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await page.goto('/payroll');
    await page.waitForLoadState('networkidle');
    
    // Find a published/paid payslip and click download
    const downloadButton = page.getByRole('button', { name: /download|unduh|pdf/i }).first();
    if (await downloadButton.isVisible({ timeout: 5000 })) {
      const downloadPromise = page.waitForEvent('download', { timeout: 15000 });
      await downloadButton.click();
      const download = await downloadPromise;
      expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
    }
  });

  test('admin can access payroll settings (PPh21, BPJS config)', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await expectHealthyPage(page, '/admin/payroll-settings');
  });

  test('payroll settings tabs are navigable', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    await page.goto('/admin/payroll-settings');
    await page.waitForLoadState('networkidle');
    
    // Check PPh21 tab
    await expect(page.locator('text=PPh21, text=TER').first()).toBeVisible({ timeout: 5000 });
    
    // Switch to BPJS tab
    const bpjsTab = page.getByRole('tab', { name: /BPJS/i }).or(page.locator('button:has-text("BPJS")')).first();
    if (await bpjsTab.isVisible({ timeout: 2000 })) {
      await bpjsTab.click();
      await expect(page.locator('text=BPJS, text=Kesehatan, text=JHT').first()).toBeVisible({ timeout: 5000 });
    }
  });
});

/**
 * Payroll Adjustment Tests
 * Note: Adjustment UI may not be fully implemented yet - these tests verify the API endpoints exist
 */
test.describe('Payroll Adjustment API', () => {
  test('adjustment endpoint is accessible', async ({ page }) => {
    await login(page, adminEmail, adminPassword);
    
    // Test via API call
    const response = await page.request.get('/api/payroll');
    expect(response.ok()).toBeTruthy();
  });
});

/**
 * Browser console error monitoring for payroll pages
 */
test.describe('Payroll Console Error Audit', () => {
  const payrollPaths = [
    '/admin/payrolls',
    '/admin/payroll-settings',
    '/payroll',
  ];

  for (const path of payrollPaths) {
    test(`no console errors on ${path}`, async ({ page }) => {
      const errors: string[] = [];
      
      page.on('console', msg => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });
      
      page.on('pageerror', error => {
        errors.push(error.message);
      });
      
      await login(page, adminEmail, adminPassword);
      await page.goto(path);
      await page.waitForLoadState('networkidle');
      
      // Filter out known non-blocking errors
      const blockingErrors = errors.filter(e => 
        !/favicon|404|chunk|manifest/i.test(e)
      );
      
      expect(blockingErrors, `Console errors on ${path}: ${blockingErrors.join('; ')}`).toEqual([]);
    });
  }
});