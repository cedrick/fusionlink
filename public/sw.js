/* FusionLink — cache static assets only (never HTML/PHP routes) */
const CACHE = 'fusionlink-pwa-v2';
const LEGACY_CACHES = [
  'fusionlink-pwa-v1',
  'isp-billing-cache-v6',
  'isp-billing-cache-v5',
  'isp-billing-cache-v4',
  'isp-billing-cache-v3',
  'isp-billing-cache-v2',
  'isp-billing-cache-v1',
  'isp-billing-cache',
];
const ASSETS = [
  './assets/js/fusionlink-pwa.js',
  './icon-192.png',
  './icon-512.png',
  './icon.svg',
  './manifest.webmanifest',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE).then(function (cache) {
      return cache.addAll(ASSETS).catch(function () {});
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) {
          return k !== CACHE;
        }).map(function (k) {
          return caches.delete(k);
        })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  var url = new URL(req.url);
  var path = url.pathname;

  if (path.endsWith('.php') || path.indexOf('/login') !== -1 || path.indexOf('/page') !== -1) {
    return;
  }
  if (path.indexOf('/assets/') === -1 && !path.endsWith('.png') && !path.endsWith('.svg') && !path.endsWith('.webmanifest')) {
    return;
  }

  event.respondWith(
    caches.match(req).then(function (cached) {
      return cached || fetch(req);
    })
  );
});
