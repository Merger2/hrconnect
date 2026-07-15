import { test as setup, expect } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(__dirname, '.auth');
if (!fs.existsSync(authDir)) fs.mkdirSync(authDir, { recursive: true });

const employeeFile = path.join(authDir, 'employee.json');
const hrFile = path.join(authDir, 'hr.json');
const adminFile = path.join(authDir, 'admin.json');
const managerFile = path.join(authDir, 'manager.json');
const financeFile = path.join(authDir, 'finance.json');

/**
 * Browser-based login + token fetch (robust version)
 * Login via UI, then fetch Sanctum token and store in localStorage
 */
async function loginAndStoreToken(page, email, password, storageFile) {
  await page.goto('/login');
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  
  // Wait for redirect to dashboard (or any authenticated page)
  await page.waitForURL(/\/(dashboard|home)/, { timeout: 15000 });
  
  // Fetch Sanctum token using session cookie
  const token = await page.evaluate(async () => {
    try {
      const res = await fetch('/api/v1/sanctum/token', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      if (res.ok) {
        const json = await res.json();
        if (json.data?.token) {
          localStorage.setItem('sanctum_token', json.data.token);
          return json.data.token;
        }
      }
    } catch (e) {
      console.error('Token fetch failed:', e);
    }
    return null;
  });
  
  if (!token) {
    throw new Error(`Failed to obtain Sanctum token for ${email}`);
  }
  
  // Wait for localStorage to be ready
  await page.waitForFunction(() => localStorage.getItem('sanctum_token') !== null, { timeout: 5000 });
  
  // Capture storage state (cookies + localStorage)
  await page.context().storageState({ path: storageFile });
  console.log(`Playwright: Auth successful for ${email}`);
}

setup.describe.configure({ retries: 2 });

setup('authenticate as employee', async ({ page }) => {
  await loginAndStoreToken(page, 'employee@hrconnect.test', 'Employee1234', employeeFile);
});

setup('authenticate as hr', async ({ page }) => {
  await loginAndStoreToken(page, 'hr@hrconnect.test', 'HRmanager1234', hrFile);
});

setup('authenticate as admin', async ({ page }) => {
  await loginAndStoreToken(page, 'admin@hrconnect.local', 'ChangeMe!2026', adminFile);
});

setup('authenticate as manager', async ({ page }) => {
  await loginAndStoreToken(page, 'manager@hrconnect.test', 'Manager1234!!', managerFile);
});

setup('authenticate as finance', async ({ page }) => {
  await loginAndStoreToken(page, 'finance@hrconnect.test', 'Finance1234!!', financeFile);
});
