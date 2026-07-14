/**
 * gps-locator.js — modul deteksi lokasi (sesuai README.md).
 *
 * Dipakai oleh:
 *   - master-data/branch-form (tombol "Deteksi lokasi saya")
 *   - form cabang lain yang butuh koordinat geofence
 *
 * Kenapa dipisah dari app.js:
 *   - app.js di-load global di semua layout, sehingga error geolocation
 *     bisa muncul di halaman yang gak butuh peta.
 *   - Modul ini hanya di-import oleh blade yang memang punya #branch-map.
 *
 * Catatan anti-fake GPS: akurasi divalidasi (accuracy > 1000m => warning),
 * tapi tidak memblokir supaya tetap bisa dipakai di environment pengembangan.
 */

const DEFAULT_CENTER = [-6.2088, 106.8456]; // Jakarta
const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/reverse';
export { DEFAULT_CENTER };
function emitCoords(lat, lng, accuracy = null) {
    const latEl = document.getElementById('lat-input');
    const lngEl = document.getElementById('lng-input');

    if (latEl) { latEl.value = lat; latEl.dispatchEvent(new Event('input', { bubbles: true })); }
    if (lngEl) { lngEl.dispatchEvent(new Event('input', { bubbles: true })); }

    if (window._branchMapRef) {
        window._branchMapRef.setView([lat, lng], 18);
        if (window._branchMarker) window._branchMarker.setLatLng([lat, lng]);
    }
    if (window._branchUpdateCoords) window._branchUpdateCoords(lat, lng);

    // Reverse geocoding untuk isi field alamat otomatis (best-effort).
    if (accuracy === null || accuracy <= 1000) {
        fetch(`${NOMINATIM_URL}?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`)
            .then((r) => r.json())
            .then((data) => {
                const addr = data?.display_name;
                if (addr) {
                    const addrEl = document.getElementById('address');
                    if (addrEl && !addrEl.value) {
                        addrEl.value = addr;
                        addrEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            })
            .catch(() => {});
    }

    window.HRConnectAlert?.toast({
        type: accuracy !== null && accuracy > 1000 ? 'warning' : 'success',
        message: accuracy !== null && accuracy > 1000
            ? 'Lokasi terdeteksi (akurasi rendah). Periksa kembali posisi.'
            : 'Lokasi terdeteksi',
    });
}

function showLocationError(err) {
    const messages = {
        1: 'Izin lokasi ditolak. Buka pengaturan browser untuk mengizinkan akses lokasi.',
        2: 'Lokasi tidak tersedia. Pastikan GPS/Location Service aktif.',
        3: 'Waktu mendeteksi lokasi habis. Coba lagi.',
    };
    const msg = messages[err?.code] || (err?.message || 'Gagal mendeteksi lokasi.');
    window.HRConnectAlert?.toast({ type: 'error', message: msg });
}

export function detectLocation() {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
        window.HRConnectAlert?.toast({
            type: 'error',
            message: 'Browser tidak mendukung geolokasi.',
        });
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = Number(pos.coords.latitude.toFixed(6));
            const lng = Number(pos.coords.longitude.toFixed(6));
            emitCoords(lat, lng, pos.coords.accuracy ?? null);
        },
        (err) => showLocationError(err),
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    );
}

export function initGpsLocator() {
    window.detectLocation = detectLocation;
    window.gpsLocatorReady = true;
}

export default { detectLocation, initGpsLocator, DEFAULT_CENTER };
