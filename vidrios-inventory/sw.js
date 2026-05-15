/**
 * Vitralia · Service Worker
 *
 * Estrategia:
 *  · Assets estáticos (css, js, img, fonts) → cache-first
 *  · Navegación HTML                        → network-first (fallback al cache)
 *  · Llamadas a fetch JSON                  → network-only (no cachear)
 */
const VERSION   = 'vitralia-v1';
const STATIC    = `${VERSION}-static`;
const RUNTIME   = `${VERSION}-runtime`;

const STATIC_ASSETS = [
    'public/css/custom.css',
    'public/js/app.js',
    'public/img/logo-removebg-preview.png',
    'manifest.webmanifest'
];

const SCOPE = self.registration.scope; // termina en /

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC)
            .then((cache) => cache.addAll(STATIC_ASSETS.map((p) => SCOPE + p)))
            .then(() => self.skipWaiting())
            .catch(() => null)
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Sólo manejamos peticiones de nuestro origen
    if (url.origin !== self.location.origin) return;

    const accept = req.headers.get('accept') || '';
    const isHtml = req.mode === 'navigate' || accept.includes('text/html');
    const isJson = accept.includes('application/json');

    if (isJson) {
        // No cachear endpoints JSON (stock, cascade, etc.)
        return;
    }

    if (isHtml) {
        event.respondWith(networkFirst(req));
        return;
    }

    // Assets: cache-first
    event.respondWith(cacheFirst(req));
});

async function cacheFirst(req) {
    const cached = await caches.match(req);
    if (cached) return cached;
    try {
        const res = await fetch(req);
        if (res && res.ok && res.type === 'basic') {
            const cache = await caches.open(RUNTIME);
            cache.put(req, res.clone());
        }
        return res;
    } catch (err) {
        return cached || Response.error();
    }
}

async function networkFirst(req) {
    try {
        const res = await fetch(req);
        if (res && res.ok) {
            const cache = await caches.open(RUNTIME);
            cache.put(req, res.clone());
        }
        return res;
    } catch (err) {
        const cached = await caches.match(req);
        return cached || new Response(
            '<h1>Sin conexión</h1><p>Esta vista no está disponible offline aún.</p>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' }, status: 503 }
        );
    }
}
