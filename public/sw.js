const CACHE_NAME = 'nutrimax-v3';
const urlsToCache = [
  './',
  './src/index.html',
  './src/dashboard.html',
  './src/food-log.html',
  './src/goals.html',
  './src/ai-coach.html',
  './src/recipes.html',
  './src/stats.html',
  './src/css/styles.css',
  './src/js/app.js',
  './assets/img/launchericon-192x192.png',
  './assets/img/launchericon-512x512.png'
];

self.addEventListener('install', event => {
  self.skipWaiting(); 
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
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    fetch(event.request).then(networkResponse => {
      if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, responseToCache);
        });
      }
      return networkResponse;
    }).catch(() => {
      return caches.match(event.request);
    })
  );
});
