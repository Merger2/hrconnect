import { chromium } from 'playwright';

const results = [];
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await ctx.newPage();

try {
  // Login employee
  await page.goto('http://localhost:8000/login', { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page.fill('input[type=email], input[name=email], #email', 'employee@hrconnect.test');
  await page.fill('input[type=password], input[name=password], #password', 'password');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => {}),
    page.click('button[type=submit]'),
  ]);
  results.push('login -> ' + (page.url().includes('/login') ? 'GAGAL' : page.url()));

  const pages = [
    ['/my-schedule', 'Jadwal'],
    ['/attendance-history', 'Riwayat Absensi'],
    ['/payroll', 'Gaji'],
    ['/apply-leave', 'Form Cuti'],
    ['/overtime', 'Lembur'],
  ];

  for (const [path, name] of pages) {
    const resp = await page.goto('http://localhost:8000' + path, { waitUntil: 'domcontentloaded', timeout: 20000 });
    const status = resp ? resp.status() : 'no-resp';
    // Ambil sedikit teks untuk bukti data tampil
    const body = await page.evaluate(() => document.body.innerText.slice(0, 400).replace(/\n+/g, ' | '));
    results.push(`${name} (${path}) -> ${status}${status >= 200 && status < 500 ? ' OK' : ' FAIL'}`);
    if (status >= 200 && status < 500) {
      results.push(`   text: ${body.slice(0, 180)}`);
    }
  }
} catch (e) {
  results.push('THROW: ' + e.message.slice(0, 150));
}

await browser.close();
console.log(results.join('\n'));
