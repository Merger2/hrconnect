import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

function resolveVendorChunk(id) {
    if (!id.includes('node_modules')) {
        return undefined;
    }

    if (id.includes('/leaflet/') || id.includes('/leaflet.markercluster/')) {
        return 'vendor-maps';
    }

    if (id.includes('/chart.js/')) {
        return 'vendor-charts';
    }

    if (
        id.includes('/sweetalert2/') ||
        id.includes('/tom-select/') ||
        id.includes('/flatpickr/') ||
        id.includes('/@orchidjs/')
    ) {
        return 'vendor-ui';
    }

    if (
        id.includes('/@capacitor-community/barcode-scanner/') ||
        id.includes('/@zxing/')
    ) {
        return 'vendor-scanner';
    }

    if (id.includes('/@capacitor/geolocation/')) {
        return 'vendor-geolocation';
    }

    if (id.includes('/@dewakoding/')) {
        return 'vendor-native-optional';
    }

    if (
        id.includes('/@capacitor/') ||
        id.includes('/@capacitor-community/')
    ) {
        return 'vendor-native';
    }

    return 'vendor-core';
}

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'icon-192.png', 'icon-512.png', 'offline.html'],
            manifest: {
                name: 'HRConnect',
                short_name: 'HRConnect',
                description: 'Enterprise HRIS — Attendance, Leave, Payroll, Knowledge Base',
                theme_color: '#0a0a0a',
                background_color: '#0a0a0a',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/login',
                lang: 'id-ID',
                categories: ['business', 'productivity', 'hr'],
                icons: [
                    {
                        src: '/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
                screenshots: [
                    {
                        src: '/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        form_factor: 'narrow',
                    },
                    {
                        src: '/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        form_factor: 'wide',
                    },
                ],
                shortcuts: [
                    {
                        name: 'Dashboard',
                        short_name: 'Home',
                        description: 'Go to Dashboard',
                        url: '/dashboard',
                        icons: [{ src: '/icon-192.png', sizes: '192x192', type: 'image/png' }],
                    },
                    {
                        name: 'Absensi',
                        short_name: 'Scan',
                        description: 'Clock In / Out',
                        url: '/attendance',
                        icons: [{ src: '/icon-192.png', sizes: '192x192', type: 'image/png' }],
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                        },
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'gstatic-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                        },
                    },
                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp|ico|webp)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                ],
                navigateFallback: '/offline.html',
            },
            devOptions: {
                enabled: true,
                type: 'clear-screen',
            },
        })
    ],
    css: {
        devSourcemap: true,
    },
    server: {
        host: '127.0.0.1',
        cors: true,
        hmr: {
            host: '127.0.0.1',
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    return resolveVendorChunk(id);
                }
            },
        },
    },
});