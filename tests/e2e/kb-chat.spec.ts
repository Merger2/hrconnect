import { expect, test, type Page } from '@playwright/test';

// Jawaban chat bisa memakan waktu (streaming Gemini atau fallback saat rate
// limited) — test butuh lebih dari default 30s.
test.setTimeout(150_000);

/**
 * E2E Knowledge Base RAG (chromium-employee).
 *
 * - Regression bug P0 (2026-08-16): index hanya menampilkan 1 dokumen karena
 *   `->unique('source_document')` menyatukan semua entry NULL source_document.
 * - Alur chat nyata (suggestion chip → jawaban). Jawaban boleh dari Gemini
 *   (online) atau fallback pg_trgm, sehingga test tetap deterministik tanpa
 *   bergantung kuota AI. Jejak sumber disimpan untuk audit backend, tetapi tidak
 *   ditampilkan pada antarmuka chat.
 */

async function openChat(page: Page) {
  await page.goto('/knowledge-base/chat');
  await expect(page.locator('textarea').first()).toBeVisible({ timeout: 15000 });
}

test('index KB menampilkan SEMUA dokumen (regression bug unique source_document)', async ({ page }) => {
  await page.goto('/knowledge-base');
  await expect(page.locator('[data-kb-card]').first()).toBeVisible({ timeout: 15000 });

  const count = await page.locator('[data-kb-card]').count();
  // Bug lama: 31 entry → 1 kartu. Sekarang semua dokumen mandiri tampil.
  expect(count).toBeGreaterThan(1);
});

test('halaman detail dokumen menampilkan isi lengkap', async ({ page }) => {
  await page.goto('/knowledge-base');
  const card = page.locator('[data-kb-card]').first();
  await expect(card).toBeVisible({ timeout: 15000 });

  await card.getByRole('link', { name: /Baca selengkapnya|Read more/ }).click();
  await page.waitForURL(/\/knowledge-base\/\d+/, { timeout: 15000 });

  // Content article di-scope ke kartu detail KB — widget layout (carousel
  // pengumuman di header app) juga memakai <article>, jadi jangan locator luas.
  const article = page.locator('section[aria-labelledby="kb-detail-title"] article');
  await expect(article).toBeVisible({ timeout: 15000 });
  const text = (await article.textContent()) ?? '';
  expect(text.trim().length).toBeGreaterThan(10);
});

test('chat: link "Tanya AI" mengisi pertanyaan (?q= prefill)', async ({ page }) => {
  await page.goto('/knowledge-base/chat?q=Apa itu cuti tahunan');
  const input = page.locator('textarea').first();
  await expect(input).toBeVisible({ timeout: 15000 });
  await expect(input).toHaveValue(/cuti/i);
});

test('chat: suggestion chip mengirim pertanyaan → hanya jawaban yang tampil', async ({ page }) => {
  await openChat(page);

  const chip = page.getByRole('button', { name: 'Apa itu cuti tahunan?' });
  await expect(chip).toBeVisible({ timeout: 15000 });
  await chip.click();

  // Pesan user tampil di bubble
  await expect(page.locator('[x-ref="messagesContainer"] .bg-primary-600')).toContainText('Apa itu cuti tahunan?', { timeout: 15000 });

  // Jawaban assistant kedua (bukan welcome) — teks non-empty, dari Gemini ATAU fallback
  const assistantBubbles = page.locator('[x-ref="messagesContainer"] .bg-gray-50');
  await expect(async () => {
    const texts = await assistantBubbles.allTextContents();
    const nonEmpty = texts.filter((t) => t.trim().length > 0);
    expect(nonEmpty.length).toBeGreaterThanOrEqual(2);
  }).toPass({ timeout: 90000, intervals: [2000] });

  expect((await assistantBubbles.allTextContents()).join(' ')).not.toMatch(/\[Sumber\s+\d+\]/);
  await expect(page.getByRole('button', { name: /Source|Sumber/ })).toHaveCount(0);
});
