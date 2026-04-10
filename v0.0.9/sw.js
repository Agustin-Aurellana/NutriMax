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

// Network-first strategy with cache fallback
self.addEventListener('fetch', event => {
  // SOLUCIÓN: Si la petición NO es un 'GET' (ej. es un POST de registro/login),
  // no hacemos nada con el caché y dejamos que siga su curso normal.
  if (event.request.method !== 'GET') {
    return; 
  }

  event.respondWith(
    fetch(event.request).then(networkResponse => {
      // Si la respuesta es buena, la guardamos en el caché
      if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME)
          .then(cache => {
            cache.put(event.request, responseToCache);
          });
      }
      return networkResponse;
    }).catch(() => {
      // Si falla la red, buscamos en el caché
      return caches.match(event.request);
    })
  );
});
