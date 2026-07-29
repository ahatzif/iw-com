const CACHE_NAME = 'com-cashier-v1';
const ASSETS = [
  'assets/css/cashier.css',
  'assets/js/cashier.js',
  'manifest.webmanifest',
  'assets/images/com-logo.svg',
  'assets/images/com-logo-light.svg',
  'assets/images/favicon-32.png',
  'assets/images/app-icon-192.png',
  'assets/images/app-icon-512.png',
  'assets/vendor/qr-scanner.min.js',
  'assets/vendor/qr-scanner-worker.min.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)).catch(() => undefined)
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.pathname.includes('/wp-json/')) {
    return;
  }

  if (request.mode === 'navigate' || request.destination === 'document') {
    event.respondWith(fetch(request));
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => cached || fetch(request))
  );
});
