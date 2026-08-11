import { defineConfig, devices } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Playwright configuration for HRConnect E2E tests
 * @see https://playwright.dev/docs/test-configuration
 *
 * Catatan 2026-08-10: project role yang mereferensikan spec legacy Paspapan /
 * email dev (approval-workflow, main-smoke, payroll, login-critical, profile,
 * post-login, test-profile, login_and_dashboard_check) dihapus bersama
 * spec-nya — kredensialnya tidak ada di DB seeder, tidak akan pernah hijau.
 * Hanya project dengan spec nyata yang dipertahankan.
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

    // Employee-authenticated tests (24 halaman user: clock-in, KB chat, payroll, dll)
    {
      name: 'chromium-employee',
      testMatch: /(employee-pages|user-tomselect)\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'employee.json'),
      },
    },

    // Admin-authenticated regression tests (tom-select persistence, dll)
    {
      name: 'chromium-admin',
      testMatch: /admin-tomselect\.spec\.ts/,
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        storageState: path.join(authDir, 'admin.json'),
        // Block service worker: SW PWA meng-intercept navigasi kedua dalam satu
        // test dan meng-abort page.goto (net::ERR_ABORTED). Test ini untuk
        // regresi form/tom-select, bukan PWA (pwa.spec.ts khusus SW).
        serviceWorkers: 'block',
      },
    },

    // PWA: manifest + service worker (guest, tanpa login)
    {
      name: 'chromium-pwa',
      testMatch: /pwa\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },

    // Auth-flow regression (email verification): login sendiri tanpa
    // storageState — user unverified dibuat helper PHP (serial mode di spec).
    {
      name: 'chromium-auth',
      testMatch: /email-verify\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
      },
    },
  ],

  // webServer disabled - server runs separately on localhost:8000
});
