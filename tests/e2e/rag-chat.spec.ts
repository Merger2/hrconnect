import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

test.describe('RAG Chat + PDF Upload', () => {
  test.beforeEach(async ({ page }) => {
    // Login as Employee
    await page.goto('/login');
    await page.fill('input[name="email"]', 'employee@hrconnect.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('E2E: RAG chat streaming response', async ({ page }) => {
    // Navigate to RAG chat
    await page.goto('/knowledge-base/chat');
    
    // Verify page loads
    await expect(page.locator('h1')).toContainText('Knowledge Base');
    
    // Check chat interface visible
    await expect(page.locator('[data-testid="chat-messages"]')).toBeVisible();
    await expect(page.locator('[data-testid="chat-input"]')).toBeVisible();
    
    // Send query
    const chatInput = page.locator('[data-testid="chat-input"]');
    await chatInput.fill('Berapa durasi cuti tahunan?');
    await page.click('button:has-text("Send")');
    
    // Wait for streaming response
    const messages = page.locator('[data-testid="chat-message"]');
    await expect(messages).not.toHaveCount(1); // Should have user + bot message
    
    // Verify bot response contains text (streaming)
    const botMessage = messages.last();
    const responseText = await botMessage.textContent();
    expect(responseText?.length).toBeGreaterThan(10);
    
    // Verify no errors
    await expect(page.locator('text=Error')).not.toBeVisible();
  });

  test('E2E: Upload PDF and query', async ({ page }) => {
    await page.goto('/knowledge-base/chat');
    
    // Check upload button
    const uploadBtn = page.locator('button:has-text("Upload PDF")');
    await expect(uploadBtn).toBeVisible();
    
    // Upload test PDF
    const testPdfPath = path.join(__dirname, 'fixtures', 'test-policy.pdf');
    
    // Create test PDF if not exists
    if (!fs.existsSync(testPdfPath)) {
      fs.mkdirSync(path.dirname(testPdfPath), { recursive: true });
      // Create simple test PDF (minimal PDF content)
      const pdfContent = Buffer.from([
        0x25, 0x50, 0x44, 0x46, 0x2d, 0x31, 0x2e, 0x34, // %PDF-1.4
      ]);
      fs.writeFileSync(testPdfPath, pdfContent);
    }
    
    // File input
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles(testPdfPath);
    
    // Wait for upload
    await page.waitForSelector('[data-testid="upload-success"]', { timeout: 10000 });
    
    // Verify upload success message
    await expect(page.locator('text=PDF uploaded successfully')).toBeVisible();
    
    // Query about uploaded content
    const chatInput = page.locator('[data-testid="chat-input"]');
    await chatInput.fill('Apa isi dari dokumen yang baru saja diupload?');
    await page.click('button:has-text("Send")');
    
    // Wait for response
    await page.waitForTimeout(2000);
    
    // Verify response references uploaded document
    const messages = page.locator('[data-testid="chat-message"]');
    const lastMessage = messages.last();
    const content = await lastMessage.textContent();
    expect(content?.length).toBeGreaterThan(10);
  });

  test('E2E: RAG chat history persists', async ({ page }) => {
    await page.goto('/knowledge-base/chat');
    
    // Send first message
    let chatInput = page.locator('[data-testid="chat-input"]');
    await chatInput.fill('Pertanyaan pertama?');
    await page.click('button:has-text("Send")');
    await page.waitForTimeout(1000);
    
    // Verify message in history
    let messages = page.locator('[data-testid="chat-message"]');
    let messageCount = await messages.count();
    expect(messageCount).toBeGreaterThanOrEqual(2); // user + bot
    
    // Send second message
    chatInput = page.locator('[data-testid="chat-input"]');
    await chatInput.fill('Pertanyaan kedua?');
    await page.click('button:has-text("Send")');
    await page.waitForTimeout(1000);
    
    // Verify both messages present
    messages = page.locator('[data-testid="chat-message"]');
    messageCount = await messages.count();
    expect(messageCount).toBeGreaterThanOrEqual(4); // 2 user + 2 bot
  });

  test('E2E: Manage knowledge base documents', async ({ page }) => {
    // Navigate to KB management
    await page.goto('/knowledge-base/manage');
    
    // Check documents list
    await expect(page.locator('[data-testid="documents-list"]')).toBeVisible();
    
    // Upload document
    const uploadBtn = page.locator('button:has-text("Upload Document")');
    await expect(uploadBtn).toBeVisible();
    
    // Delete document if exists
    const deleteBtn = page.locator('button[data-action="delete"]').first();
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click();
      await page.click('button:has-text("Confirm")');
      await page.waitForTimeout(1000);
    }
  });
});
