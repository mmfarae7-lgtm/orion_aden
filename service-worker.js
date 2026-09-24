const CACHE = 'orion-v6';

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(cache => {
      return cache.addAll([
        '/',
        '/offline.html',
        '/assets/css/style.css',
        '/assets/vendor/bootstrap/css/bootstrap.rtl.min.css',
        '/assets/vendor/bootstrap-icons/bootstrap-icons.css',
        '/assets/vendor/tajawal/tajawal.css',
        '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
        '/assets/js/script.js',
        '/assets/js/offline-queue.js',
        '/assets/img/Orion.png',
        '/assets/img/icon-192.png',
        '/assets/img/icon-512.png'
      ]).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE).map(k => caches.delete(k))
    )).then(() => clients.claim())
  );
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;

  if (e.request.mode === 'navigate') {
    e.respondWith(
      fetch(e.request).then(response => {
        const copy = response.clone();
        caches.open(CACHE).then(cache => cache.put(e.request, copy));
        return response;
      }).catch(() => caches.match(e.request).then(cached => cached || caches.match('/offline.html')))
    );
    return;
  }

  e.respondWith(
    caches.match(e.request).then(cached => {
      return cached || fetch(e.request).then(response => {
        if (response.status === 200) {
          const copy = response.clone();
          caches.open(CACHE).then(cache => cache.put(e.request, copy));
        }
        return response;
      });
    }).catch(() => caches.match('/offline.html'))
  );
});
