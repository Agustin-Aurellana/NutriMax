const CACHE_NAME = 'nutrimax-v3';
const urlsToCache = [
  './',
  './index.html',
  './dashboard.html',
  './food-log.html',
  './goals.html',
  './ai-coach.html',
  './recipes.html',
  './stats.html',
  './css/styles.css',
  './js/app.js',
  './img/launchericon-192x192.png',
  './img/launchericon-512x512.png'
];

self.addEventListener('install', event => {
  self.skipWaiting(); // Force the waiting service worker to become the active service worker.
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim()) // Claim clients immediately
  );
});

// Network-first strategy with cache fallback
self.addEventListener('fetch', event => {
  event.respondWith(
    fetch(event.request).then(networkResponse => {
      // Si la respuesta es buena, la guardamos en el caché
      if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, responseToCache);
        });
      }
      return networkResponse;
    }).catch(() => {
      // Si falla la red (offline), devolvemos desde el caché
      return caches.match(event.request);
    })
  );
});
