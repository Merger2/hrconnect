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

setup('authenticate as employee', async ({ page }) => {
  await page.goto('/login');
  await page.locator('input[name="email"]').fill('employee@hrconnect.test');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 10000 });
  await page.context().storageState({ path: employeeFile });
});

setup('authenticate as hr', async ({ page }) => {
  await page.goto('/login');
  await page.locator('input[name="email"]').fill('hr@hrconnect.test');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 10000 });
  await page.context().storageState({ path: hrFile });
});

setup('authenticate as admin', async ({ page }) => {
  await page.goto('/login');
  await page.locator('input[name="email"]').fill('admin@hrconnect.local');
  await page.locator('input[name="password"]').fill('ChangeMe!2026');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 10000 });
  await page.context().storageState({ path: adminFile });
});
