/* iBird Support Agent — Service Worker
   Cache-first for static assets; network-first for REST API calls. */
var CACHE  = 'aiagent-v1';
var STATIC = [
  '/wp-content/plugins/ai-support-agent/public/assets/widget.css',
  '/wp-content/plugins/ai-support-agent/public/assets/widget.js',
];

self.addEventListener('install', function (e) {
  self.skipWaiting();
  e.waitUntil(
    caches.open(CACHE).then(function (c) { return c.addAll(STATIC); })
  );
});

self.addEventListener('activate', function (e) {
  e.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function (e) {
  var url = e.request.url;

  // Network-first for REST API and admin-ajax calls.
  if (url.includes('/wp-json/aiagent/') || url.includes('admin-ajax.php')) {
    e.respondWith(
      fetch(e.request).catch(function () {
        return new Response(
          JSON.stringify({ reply: 'You appear to be offline. Please check your connection.', error: true }),
          { headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // Cache-first for static plugin assets.
  if (url.includes('/ai-support-agent/public/assets/')) {
    e.respondWith(
      caches.match(e.request).then(function (cached) {
        return cached || fetch(e.request).then(function (res) {
          var clone = res.clone();
          caches.open(CACHE).then(function (c) { c.put(e.request, clone); });
          return res;
        });
      })
    );
    return;
  }

  // Everything else: network passthrough.
  e.respondWith(fetch(e.request));
});
