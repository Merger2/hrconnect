import { expect, Page } from '@playwright/test';

/**
 * Smoke test a page: navigate, check no 500 error, verify body visible.
 * Returns the page title (h1 text).
 */
export async function smokeTest(page: Page, path: string): Promise<string> {
    const response = await page.goto(path, { waitUntil: 'networkidle', timeout: 15000 });

    // Should not be a 500 error
    expect(response!.status()).toBeLessThan(500);

    // Page body should be visible
    await expect(page.locator('body')).toBeVisible();

    // No exception/stack trace in page
    await expect(page.locator('body')).not.toContainText(/exception|stack trace|whoops/i);

    // Get h1 title
    const h1 = await page.locator('h1').first().textContent();
    return (h1 ?? '').trim();
}

/**
 * Generate parameterized smoke tests for a list of pages.
 * Each page gets its own test case.
 */
export function generateSmokeTests(
    pages: Array<{ path: string; titleContains: string }>,
    testFn: (path: string, titleContains: string) => void,
) {
    for (const { path, titleContains } of pages) {
        testFn(path, titleContains);
    }
}
