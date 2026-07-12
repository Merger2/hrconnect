# End-to-End Testing dengan Playwright

Playwright E2E tests untuk HRConnect - testing fitur critical seperti Face Recognition, GPS Geofencing, dan RAG Knowledge Base.

## Setup

Playwright sudah terinstall di project. Browser Chromium sudah di-download.

## Menjalankan Tests

```bash
# Run all E2E tests (headless)
npm run test:e2e

# Run dengan UI mode (interactive)
npm run test:e2e:ui

# Run dengan browser visible (debugging)
npm run test:e2e:headed

# Run dengan debugger
npm run test:e2e:debug

# Show test report
npm run test:e2e:report

# Run specific test file
npx playwright test tests/e2e/auth.spec.js

# Run specific test by name
npx playwright test -g "should login with valid credentials"
```

## Test Files

| File | Coverage |
|------|----------|
| `auth.spec.js` | Login, validation, authentication flow |
| `face-enrollment.spec.js` | Face registration, liveness detection, face-api.js |
| `clock-in.spec.js` | Clock-in dengan face verification + GPS |
| `rag-chat.spec.js` | RAG Knowledge Base chat, streaming response |

## Konfigurasi

File `playwright.config.js` di root project:

- **Base URL**: `http://localhost:8000` (dari `APP_URL` env)
- **Browser**: Chromium (desktop + mobile Pixel 5)
- **Permissions**: Camera + Geolocation granted by default
- **Auto-start**: Laravel dev server (`php artisan serve`)
- **Artifacts**: Screenshots dan video hanya pada failure

## Prerequisites untuk Testing

### 1. Database Seeder

Buat test user di seeder atau manual:

```php
User::factory()->create([
    'email' => 'test@hrconnect.test',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);
```

### 2. Dev Server Running

Playwright akan auto-start `php artisan serve`, tapi bisa juga manual:

```bash
composer run dev  # atau php artisan serve
```

### 3. Vite Build (untuk asset testing)

```bash
npm run build  # atau npm run dev di terminal terpisah
```

## Testing Face Recognition & GPS

Tests untuk Face Recognition dan GPS memerlukan **mock** atau **bypass** karena di CI environment tidak ada camera/GPS real:

### Opsi 1: Mock di Test
```javascript
// Mock camera stream
await page.addInitScript(() => {
  navigator.mediaDevices.getUserMedia = async () => {
    // Return fake video stream
  };
});
```

### Opsi 2: Bypass Mode (Testing ENV)
Set environment variable di CI untuk bypass face verification:
```bash
TESTING_MODE=true npm run test:e2e
```

## Debugging

### Visual Debugging
```bash
npm run test:e2e:debug
```

### Inspect Element State
```bash
npx playwright test --headed --debug
```

### Trace Viewer (post-mortem)
Traces auto-saved on retry. View dengan:
```bash
npx playwright show-trace trace.zip
```

## CI Integration

Tambahkan ke `.github/workflows/tests.yml`:

```yaml
- name: Install Playwright Browsers
  run: npx playwright install chromium

- name: Run E2E Tests
  run: npm run test:e2e
  env:
    APP_URL: http://localhost:8000
    TESTING_MODE: true
```

## Best Practices

1. **Selectors**: Prefer `getByRole`, `getByText` dibanding CSS selectors
2. **Waits**: Use `expect().toBeVisible()` dengan timeout, hindari `waitForTimeout`
3. **Isolation**: Setiap test harus independent (login di `beforeEach`)
4. **Cleanup**: Use transactions atau reset database setelah test
5. **Assertions**: Multiple assertions per test OK (bukan seperti unit test)

## Troubleshooting

### Camera Permission Denied
Sudah di-grant via config. Jika masih error, cek browser security settings.

### Video Element Not Found
Face-api.js mungkin belum load. Tambah timeout atau wait for `window.faceapi`:

```javascript
await page.waitForFunction(() => typeof window.faceapi !== 'undefined');
```

### GPS Coordinates Wrong
Default Jakarta (-6.2088, 106.8456). Override per-test:

```javascript
await context.setGeolocation({ latitude: -7.250445, longitude: 112.768845 });
```

### Laravel Dev Server Port Conflict
Ganti di `playwright.config.js`:
```javascript
webServer: {
  command: 'php artisan serve --port=8001',
  url: 'http://localhost:8001',
}
```
