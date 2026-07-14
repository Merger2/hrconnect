import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30000,
  use: {
    baseURL: 'http://localhost:8000',
    trace: 'on',
  },
  projects: [
    {
      name: 'audit',
      use: { ...devices['Desktop Chrome'] },
      testMatch: /audit-console\.spec\.ts/,
    },
  ],
});
