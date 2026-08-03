import { defineConfig, devices } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Playwright configuration for HRConnect E2E tests
 * @see https://playwright.dev/docs/test-configuration
 */
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(__dirname, 'tests/e2e/.auth');

export default defineConfig({
  testDir: './tests/e2e',

  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['html', { open: 'never' }], ['list']],

  use: {
    baseURL: process.env.APP_URL || 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  projects: [
    {
      name: 'setup',
      testMatch: /auth\.setup\.ts/,
      workers: 1,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    // Employee-authenticated tests (clock-in, KB chat, loans, overtime, full role coverage)
    {
      name: 'chromium-employee',
      testMatch: /employee-pages\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'employee.json'),
      },
    },

    // HR-authenticated tests (face enrollment, master data, full role coverage)
    {
      name: 'chromium-hr',
      testMatch: /(face-enrollment|master-data|role-hr)\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'hr.json'),
      },
    },

    // Manager-authenticated tests
    {
      name: 'chromium-manager',
      testMatch: /role-manager\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'manager.json'),
      },
    },

    // Finance-authenticated tests
    {
      name: 'chromium-finance',
      testMatch: /role-finance\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'finance.json'),
      },
    },

    // Super-Admin authenticated tests (employee admin, payroll settings, approval, reimbursement)
    {
      name: 'chromium-admin',
      testMatch: /(employee|payroll-settings|reimbursement|approval|monitoring|payroll-config|auth-enhanced|face-recognition-api|approval-workflow|role-super-admin)\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'admin.json'),
      },
    },

    // Cross-role console, page-error, and network audit
    {
      name: 'chromium-audit',
      testMatch: /console-network-audit\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    {
      name: 'chromium-pwa',
      testMatch: /pwa\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    {
      name: 'chromium-ux',
      testMatch: /user-experience\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    {
      name: 'chromium-profile',
      testMatch: /profile\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    {
      name: 'chromium-auth',
      testMatch: /(auth|login-flow-test|login-critical)\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },
  ],

  // webServer disabled - server runs separately on localhost:8000
});