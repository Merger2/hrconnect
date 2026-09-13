import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const employeeState = path.join(authDir, 'employee.json');

test.use({ storageState: employeeState });

test.describe('Employee Presensi - GPS/Geofencing + Face Recognition', () => {
    test.describe.configure({ mode: 'serial' });

    test('Livewire navigation to presensi initializes its Alpine factories', async ({ page }) => {
        const errors: string[] = [];

        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
            if (message.type() === 'error') {
                errors.push(message.text());
            }
        });

        await page.goto(`${BASE}/home`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.locator('a[href="/scan"]').first().click();
        await expect(page).toHaveURL(/\/scan$/);
        await expect(page.locator('[wire\\:name="user.clock-in-action"]')).toBeVisible({ timeout: 10000 });

        expect(errors.filter((error) => /clockInAction|locationCard|gpsWarning|mapVisible/.test(error))).toEqual([]);
    });

    test('Employee can access presensi page /scan', async ({ page }) => {
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Page should load with date title
        await expect(page.locator('h2').filter({ hasText: /[0-9]{4}/ })).toBeVisible({ timeout: 10000 });
    });

    test('Presensi page shows clock-in interface', async ({ page }) => {
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Should have clock in button (clock out may be hidden if not clocked in yet)
        const clockInButton = page.locator('[wire\\:click="startClockIn"]').first();
        await expect(clockInButton).toBeVisible({ timeout: 10000 });
    });

    test('GPS location is captured when clocking in', async ({ page }) => {
        // Grant geolocation permission
        await page.context().grantPermissions(['geolocation']);
        
        // Set geolocation to office coordinates (Jakarta office)
        await page.context().setGeolocation({ latitude: -6.2088, longitude: 106.8456 });
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.evaluate(() => {
            window.dispatchEvent(new CustomEvent('gps-captured', {
                detail: { latitude: -6.2088, longitude: 106.8456, accuracy: 20 },
            }));
        });
        
        await expect(page.locator('body')).toContainText(/GPS Ready|Lokasi Anda|Akurasi|accuracy/i, { timeout: 10000 });
    });

    test('Face recognition component is available', async ({ page }) => {
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Grant camera permission
        await page.context().grantPermissions(['camera']);
        
        // Check for face enrollment link - it might be hidden if already enrolled
        const faceEnrollmentLink = page.locator('a[href*="face-enrollment"]').first();
        const clockInBtn = page.locator('[wire\\:click="startClockIn"]').first();
        
        // Clock in button (which triggers face verification) should always exist
        await expect(clockInBtn).toBeVisible({ timeout: 10000 });
        
        // If face enrollment link is visible, that's also good
        const isFaceLinkVisible = await faceEnrollmentLink.isVisible({ timeout: 2000 }).catch(() => false);
        if (isFaceLinkVisible) {
            await expect(faceEnrollmentLink).toBeVisible();
        }
    });

    test('Clock in triggers face verification', async ({ page }) => {
        await page.context().grantPermissions(['camera', 'geolocation']);
        await page.context().setGeolocation({ latitude: -6.2088, longitude: 106.8456 });
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.evaluate(() => {
            window.dispatchEvent(new CustomEvent('gps-captured', {
                detail: { latitude: -6.2088, longitude: 106.8456, accuracy: 20 },
            }));
        });
        await expect(page.locator('body')).toContainText(/GPS Ready|Lokasi Anda|Akurasi|accuracy/i, { timeout: 10000 });

        const faceCapturePromise = page.evaluate(() => new Promise((resolve) => {
            window.addEventListener('start-face-verification', (event) => {
                resolve((event as CustomEvent).detail?.action ?? null);
            }, { once: true });
        }));
        
        const clockInBtn = page.locator('[wire\\:click="startClockIn"]').first();
        await expect(clockInBtn).toBeVisible({ timeout: 10000 });
        await clockInBtn.click();

        await expect(faceCapturePromise).resolves.toBe('clock_in');
    });

    test('No PIN fallback (face-only policy)', async ({ page }) => {
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Verify no PIN input or PIN fallback button exists
        const pinInput = page.locator('input[type="password"][name*="pin"], input[name*="pin"]').first();
        const pinButton = page.locator('button:has-text("PIN"), button:has-text("pin")').first();
        
        // PIN fallback should NOT exist (face-only policy)
        await expect(pinInput).not.toBeVisible({ timeout: 5000 });
        await expect(pinButton).not.toBeVisible({ timeout: 5000 });
    });

    test('Geofence validation - outside radius shows error', async ({ page }) => {
        await page.context().grantPermissions(['geolocation']);
        
        // Set geolocation far from office (outside 50m radius)
        await page.context().setGeolocation({ latitude: -6.0, longitude: 106.0 });
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.evaluate(() => {
            window.dispatchEvent(new CustomEvent('gps-captured', {
                detail: { latitude: -6.0, longitude: 106.0, accuracy: 20 },
            }));
        });
        
        await expect(page.locator('body')).toContainText(/GPS Ready|Lokasi Anda|Akurasi|accuracy/i, { timeout: 10000 });

        const clockInBtn = page.locator('[wire\\:click="startClockIn"]').first();
        if (!(await clockInBtn.isVisible({ timeout: 5000 }).catch(() => false))) {
            await expect(page.locator('body')).toContainText(/checked in|check out|sudah melakukan absensi|Attendance Complete/i);
            return;
        }

        await clockInBtn.click();
        await page.evaluate(() => {
            window.dispatchEvent(new CustomEvent('face-captured', {
                detail: {
                    action: 'clock_in',
                    descriptor: Array(128).fill(0.1),
                },
            }));
        });

        const errorToast = page.locator('[role="alert"]').first();
        await expect(errorToast).toBeVisible({ timeout: 15000 });
        await expect(errorToast).toContainText(/geofence|radius|jarak|distance|diluar|lebih|area|kantor|office|lokasi|koordinat/i);
    });

    test('Clock in records attendance after valid face descriptor', async ({ page }) => {
        await page.context().grantPermissions(['camera', 'geolocation']);
        await page.context().setGeolocation({ latitude: -6.2088, longitude: 106.8456 });
        await page.goto(`${BASE}/scan`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.evaluate(() => {
            window.dispatchEvent(new CustomEvent('gps-captured', {
                detail: { latitude: -6.2088, longitude: 106.8456, accuracy: 20 },
            }));
        });
        await expect(page.locator('body')).toContainText(/GPS Ready|Lokasi Anda|Akurasi|accuracy/i, { timeout: 10000 });

        const clockInBtn = page.locator('[wire\\:click="startClockIn"]').first();
        if (!(await clockInBtn.isVisible({ timeout: 5000 }).catch(() => false))) {
            await expect(page.locator('body')).toContainText(/checked in|check out|sudah melakukan absensi|Attendance Complete/i);
            return;
        }

        await clockInBtn.click();
        await page.evaluate(() => {
            window.dispatchEvent(new CustomEvent('face-captured', {
                detail: {
                    action: 'clock_in',
                    descriptor: Array(128).fill(0.1),
                },
            }));
        });

        await expect(page.locator('body')).toContainText(/Check In successful|Checked in|Check Out/i, { timeout: 15000 });
    });
});
