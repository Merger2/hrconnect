import { test, expect } from '@playwright/test';

test.describe('RAG Knowledge Base Chat', () => {
  test.beforeEach(async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.locator('input[type="email"]').fill('employee@hrconnect.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /login|masuk/i }).click();
    await page.waitForURL(/\/dashboard/, { timeout: 5000 });
  });

  test('should display knowledge base chat page', async ({ page }) => {
    await page.goto('/knowledge-base/chat');
    
    // Check for chat interface
    await expect(page.locator('input[type="text"], textarea')).toBeVisible();
    await expect(page.getByRole('button', { name: /send|kirim/i })).toBeVisible();
  });

  test('should show FAQ suggestions', async ({ page }) => {
    await page.goto('/knowledge-base/chat');
    
    // Check for FAQ suggestion buttons
    await expect(page.locator('button:has-text("Cuti"), button:has-text("Gaji"), button:has-text("Lembur")')).toHaveCount(3, { timeout: 3000 });
  });

  test('should send message and receive response', async ({ page }) => {
    await page.goto('/knowledge-base/chat');
    
    const chatInput = page.locator('input[type="text"], textarea').first();
    const sendButton = page.getByRole('button', { name: /send|kirim/i });
    
    // Type a question
    await chatInput.fill('Bagaimana cara mengajukan cuti?');
    await sendButton.click();
    
    // Should show user message
    await expect(page.locator('text=Bagaimana cara mengajukan cuti?')).toBeVisible();
    
    // Should show AI response (with streaming)
    await expect(page.locator('[data-role="assistant"], .assistant-message')).toBeVisible({ timeout: 10000 });
  });

  test('should handle streaming response', async ({ page }) => {
    await page.goto('/knowledge-base/chat');
    
    const chatInput = page.locator('input[type="text"], textarea').first();
    await chatInput.fill('Jelaskan tentang sistem payroll');
    await chatInput.press('Enter');
    
    // Wait for streaming to start
    await page.waitForTimeout(2000);
    
    // Check that response is being built (adjust selector)
    const responseContainer = page.locator('[data-role="assistant"], .assistant-message').last();
    await expect(responseContainer).toBeVisible();
    
    // Text should grow as streaming continues
    const initialLength = (await responseContainer.textContent()).length;
    await page.waitForTimeout(1000);
    const laterLength = (await responseContainer.textContent()).length;
    
    expect(laterLength).toBeGreaterThan(initialLength);
  });
});
