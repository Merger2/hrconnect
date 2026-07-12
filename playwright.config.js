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

  /* Run tests in files in parallel */
  fullyParallel: true,

  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,

  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,

  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,

  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [['html', { open: 'never' }], ['list']],

  /* Shared settings for all the projects below. */
  use: {
    baseURL: process.env.APP_URL || 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  /* Configure projects for major browsers */
  projects: [
    // Setup project — logs in once per role, saves storageState (avoids login throttle)
    {
      name: 'setup',
      testMatch: /auth\.setup\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    // Employee-authenticated tests (clock-in, KB chat, loans, overtime)
    {
      name: 'chromium-employee',
      testMatch: /(clock-in|rag-chat|loans|overtime)\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'employee.json'),
      },
    },

    // HR-authenticated tests (face enrollment, master data)
    {
      name: 'chromium-hr',
      testMatch: /(face-enrollment|master-data)\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'hr.json'),
      },
    },

    // Auth flow tests (login/logout) — no stored state, tests login itself
    {
      name: 'chromium-auth',
      testMatch: /auth\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'php artisan serve',
    url: 'http://localhost:8000',
    reuseExistingServer: !process.env.CI,
    stdout: 'ignore',
    stderr: 'pipe',
  },
});
