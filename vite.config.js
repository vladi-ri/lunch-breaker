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
                // Without this, workbox-build's generateSW defaults to injecting its
                // own NavigationRoute for a nonexistent "index.html" (an SPA-style
                // default) - registered before our own rule below, so it would win
                // for every navigation (including POSTs, since it has no method
                // restriction) despite offline.html being what we actually want.
                navigateFallback: null,
                // `navigateFallback` (the simpler built-in option) doesn't
                // distinguish HTTP methods, so it was serving offline.html for
                // POST form submissions (register, login, RSVP, ...) even when
                // the network request actually succeeded - the Cache API can't
                // store a non-GET request, so trying to do so throws and gets
                // treated as a failure. This rule is scoped to GET explicitly,
                // so POST/PUT/DELETE navigations bypass the service worker
                // entirely and hit the network normally.
                runtimeCaching: [
                    {
                        urlPattern: ({ request, url }) =>
                            request.mode === 'navigate'
                            && !/^\/(admin|settings)/.test(url.pathname),
                        method: 'GET',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages',
                            networkTimeoutSeconds: 3,
                            plugins: [
                                {
                                    handlerDidError: async () => caches.match('/offline.html'),
                                },
                            ],
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
