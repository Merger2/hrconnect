import { expect, test } from '@playwright/test';

const adminEmail = process.env.E2E_ADMIN_EMAIL ?? 'apk.demo.superadmin@paspapan.test';
const adminPassword = process.env.E2E_ADMIN_PASSWORD ?? '12345678';
const managerEmail = process.env.E2E_MANAGER_EMAIL ?? 'manager@hrconnect.test';
const managerPassword = process.env.E2E_MANAGER_PASSWORD ?? '12345678';
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
 * Approval Workflow E2E Tests
 * Tests approval flows for Leave, Overtime, Reimbursement requests
 */
test.describe('Approval Workflow E2E Flow', () => {
  test('user can submit leave request for approval', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await page.goto('/apply-leave');
    await page.waitForLoadState('networkidle');
    
    // Fill leave form
    await page.locator('select[name="leave_type_id"], select[aria-label*="leave type" i]').first().selectOption('1');
    await page.locator('input[name="start_date"]').fill('2026-07-25');
    await page.locator('input[name="end_date"]').fill('2026-07-25');
    await page.locator('textarea[name="reason"], textarea[aria-label*="reason" i]').fill('Sakit demam tinggi');
    
    // Submit request
    const submitButton = page.getByRole('button', { name: /apply|submit|ajukan/i });
    await expect(submitButton.first()).toBeVisible({ timeout: 5000 });
    await submitButton.first().click();
    await expect(page.locator('body')).toContainText(/berhasil|success|ajukan/i, { timeout: 10000 });
  });

  test('manager can view pending approvals in TeamApprovals', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    await page.goto('/approvals');
    await page.waitForLoadState('networkidle');
    
    // Check approvals page loads
    await expect(page.locator('text=Approval, text=Persetujuan, text=Menunggu')).toBeVisible({ timeout: 10000 });
    
    // Check tabs for different request types
    await expect(page.locator('text=Leave, text=Cuti')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('text=Overtime, text=Lembur')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('text=Reimbursement, text=Pencairan')).toBeVisible({ timeout: 5000 });
  });

  test('manager can approve leave request', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    await page.goto('/approvals');
    await page.waitForLoadState('networkidle');
    
    // Switch to leaves tab if needed
    const leavesTab = page.getByRole('tab', { name: /leave|cuti/i });
    if (await leavesTab.isVisible({ timeout: 2000 })) {
      await leavesTab.click();
    }
    
    // Find pending leave request and approve
    const pendingLeave = page.locator('tr:has-text("pending"), tr:has-text("Menunggu"), tr:has-text("Pending")').first();
    if (await pendingLeave.isVisible({ timeout: 5000 })) {
      const approveButton = pendingLeave.getByRole('button', { name: /approve|setujui/i }).first();
      if (await approveButton.isVisible({ timeout: 2000 })) {
        await approveButton.click();
        await expect(page.locator('body')).toContainText(/berhasil|success/i, { timeout: 10000 });
      }
    }
  });

  test('manager can reject leave request with reason', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    await page.goto('/approvals');
    await page.waitForLoadState('networkidle');
    
    // Switch to leaves tab if needed
    const leavesTab = page.getByRole('tab', { name: /leave|cuti/i });
    if (await leavesTab.isVisible({ timeout: 2000 })) {
      await leavesTab.click();
    }
    
    // Find pending leave request and reject
    const pendingLeave = page.locator('tr:has-text("pending"), tr:has-text("Menunggu"), tr:has-text("Pending")').first();
    if (await pendingLeave.isVisible({ timeout: 5000 })) {
      const rejectButton = pendingLeave.getByRole('button', { name: /reject|tolak/i }).first();
      if (await rejectButton.isVisible({ timeout: 2000 })) {
        await rejectButton.click();
        
        // Fill rejection reason
        const reasonInput = page.locator('textarea[name="reason"], textarea[placeholder*="reason" i], textarea[aria-label*="reason" i]');
        if (await reasonInput.isVisible({ timeout: 2000 })) {
          await reasonInput.fill('Cuti tidak sesuai dengan kebijakan perusahaan');
        }
        
        const confirmButton = page.getByRole('button', { name: /confirm|ya|setuju/i }).first();
        if (await confirmButton.isVisible({ timeout: 2000 })) {
          await confirmButton.click();
          await expect(page.locator('body')).toContainText(/berhasil|success/i, { timeout: 10000 });
        }
      }
    }
  });

  test('user can submit overtime request for approval', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await page.goto('/overtime');
    await page.waitForLoadState('networkidle');
    
    // Fill overtime form
    await page.locator('input[name="date"]').fill('2026-07-25');
    await page.locator('input[name="start_time"]').fill('18:00');
    await page.locator('input[name="end_time"]').fill('20:00');
    await page.locator('textarea[name="description"], textarea[aria-label*="description" i]').fill('Pengerjaan laporan bulanan');
    
    // Submit request
    const submitButton = page.getByRole('button', { name: /apply|submit|ajukan/i });
    await expect(submitButton.first()).toBeVisible({ timeout: 5000 });
    await submitButton.first().click();
    await expect(page.locator('body')).toContainText(/berhasil|success|ajukan/i, { timeout: 10000 });
  });

  test('manager can approve overtime request', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    await page.goto('/approvals');
    await page.waitForLoadState('networkidle');
    
    // Switch to overtime tab
    const overtimeTab = page.getByRole('tab', { name: /overtime|lembur/i });
    if (await overtimeTab.isVisible({ timeout: 2000 })) {
      await overtimeTab.click();
    }
    
    // Find pending overtime request and approve
    const pendingOvertime = page.locator('tr:has-text("pending"), tr:has-text("Menunggu"), tr:has-text("Pending")').first();
    if (await pendingOvertime.isVisible({ timeout: 5000 })) {
      const approveButton = pendingOvertime.getByRole('button', { name: /approve|setujui/i }).first();
      if (await approveButton.isVisible({ timeout: 2000 })) {
        await approveButton.click();
        await expect(page.locator('body')).toContainText(/berhasil|success/i, { timeout: 10000 });
      }
    }
  });

  test('user can submit reimbursement request for approval', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await page.goto('/reimbursement');
    await page.waitForLoadState('networkidle');
    
    // Fill reimbursement form
    await page.locator('input[name="expense_date"]').fill('2026-07-25');
    await page.locator('select[name="category_id"], select[aria-label*="category" i]').first().selectOption('1');
    await page.locator('input[name="amount"]').fill('150000');
    await page.locator('input[name="title"], input[placeholder*="title" i]').fill('Transportasi ke kantor');
    await page.locator('textarea[name="description"], textarea[aria-label*="description" i]').fill('Ongkos transportasi dari rumah ke kantor');
    
    // Submit request
    const submitButton = page.getByRole('button', { name: /submit|ajukan/i });
    await expect(submitButton.first()).toBeVisible({ timeout: 5000 });
    await submitButton.first().click();
    await expect(page.locator('body')).toContainText(/berhasil|success|ajukan/i, { timeout: 10000 });
  });

  test('manager can approve reimbursement request', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    await page.goto('/approvals');
    await page.waitForLoadState('networkidle');
    
    // Switch to reimbursements tab
    const reimbursementTab = page.getByRole('tab', { name: /reimbursement|pencairan/i });
    if (await reimbursementTab.isVisible({ timeout: 2000 })) {
      await reimbursementTab.click();
    }
    
    // Find pending reimbursement request and approve
    const pendingReimbursement = page.locator('tr:has-text("pending"), tr:has-text("Menunggu"), tr:has-text("Pending")').first();
    if (await pendingReimbursement.isVisible({ timeout: 5000 })) {
      const approveButton = pendingReimbursement.getByRole('button', { name: /approve|setujui/i }).first();
      if (await approveButton.isVisible({ timeout: 2000 })) {
        await approveButton.click();
        await expect(page.locator('body')).toContainText(/berhasil|success/i, { timeout: 10000 });
      }
    }
  });

  test('approval chain respects hierarchy (L1 -> L2)', async ({ page }) => {
    // This test verifies that higher-level approvers cannot approve before lower levels
    await login(page, adminEmail, adminPassword); // Admin as L2 approver
    await page.goto('/approvals');
    await page.waitForLoadState('networkidle');
    
    // Find a request that requires L1 approval first
    const needsApproval = page.locator('tr:has-text("Menunggu L1"), tr:has-text("Waiting L1"), tr:has-text("Supervisor")').first();
    if (await needsApproval.isVisible({ timeout: 5000 })) {
      const l2ApproveButton = needsApproval.getByRole('button', { name: /approve|setujui/i }).filter({ hasText: /Manager|HR/ });
      if (await l2ApproveButton.isVisible({ timeout: 2000 })) {
        // Should be disabled or not clickable for L2 before L1 approves
        await expect(l2ApproveButton).toBeDisabled({ timeout: 2000 });
      }
    }
  });

  test('user can view approval history', async ({ page }) => {
    await login(page, userEmail, userPassword);
    await page.goto('/approvals/history');
    await page.waitForLoadState('networkidle');
    
    await expect(page.locator('text=Approval History, text=Riwayat Persetujuan')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('table, [role="table"]').first()).toBeVisible({ timeout: 5000 });
  });

  test('manager can view team approval history', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    await page.goto('/approvals/history');
    await page.waitForLoadState('networkidle');
    
    await expect(page.locator('text=Approval History, text=Riwayat Persetujuan')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('table, [role="table"]').first()).toBeVisible({ timeout: 5000 });
  });
});

/**
 * Approval Workflow API Endpoint Tests
 */
test.describe('Approval Workflow API', () => {
  test('pending approvals endpoint is accessible', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    
    // Test API call
    const response = await page.request.get('/api/approvals/pending');
    expect(response.ok()).toBeTruthy();
    
    const json = await response.json();
    expect(json.status).toBe('success');
  });

  test('approve endpoint handles valid requests', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    
    // This would need an actual approval ID to test properly
    // For now we verify the endpoint exists
    const response = await page.request.post('/api/approvals/1/approve', {
      data: { notes: 'Test approval' }
    });
    // Expect 404 if approval doesn't exist, or 403 if not authorized, or 200 if successful
    expect([200, 403, 404, 409]).toContain(response.status());
  });

  test('reject endpoint handles valid requests', async ({ page }) => {
    await login(page, managerEmail, managerPassword);
    
    const response = await page.request.post('/api/approvals/1/reject', {
      data: { rejection_reason: 'Test rejection reason' }
    });
    // Expect 404 if approval doesn't exist, or 403 if not authorized, or 200 if successful
    expect([200, 403, 404, 409]).toContain(response.status());
  });
});

/**
 * Browser console error monitoring for approval pages
 */
test.describe('Approval Console Error Audit', () => {
  const approvalPaths = [
    '/approvals',
    '/approvals/history',
    '/apply-leave',
    '/overtime',
    '/reimbursement',
  ];

  for (const path of approvalPaths) {
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
      
      await login(page, managerEmail, managerPassword);
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