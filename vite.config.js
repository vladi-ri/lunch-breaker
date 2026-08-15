import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            // Vite/Laravel emit built assets (and this plugin's sw.js/manifest) under
            // public/build/ (Vite's default base here). The service worker still needs
            // to control the whole origin, so `scope` is set to root separately from
            // `base` — the browser honors that only because public/.htaccess sends a
            // `Service-Worker-Allowed: /` header for /build/sw.js (see public/.htaccess).
            scope: '/',
            registerType: 'autoUpdate',
            injectRegister: false, // registered manually in resources/js/app.js (no index.html entry to auto-inject into)
            manifest: {
                name: 'Lunch Breaker',
                short_name: 'Lunch Breaker',
                description: 'Find nearby lunch spots and menus, and eat with your colleagues.',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#2563eb',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/icon-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globDirectory: 'public/build',
                globPatterns: ['**/*.{js,css}'],
                additionalManifestEntries: [
                    { url: '/offline.html', revision: '1' },
                ],
                navigateFallback: '/offline.html',
                navigateFallbackDenylist: [/^\/admin/, /^\/settings/],
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages',
                            networkTimeoutSeconds: 3,
                        },
                    },
                ],
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
});
