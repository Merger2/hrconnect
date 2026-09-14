const CACHE = "hrconnect-v2";
const STATIC = ["/offline", "/manifest.json"];

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(STATIC)));
  self.skipWaiting();
});

self.addEventListener("activate", (e) => {
  // Bersihkan cache versi lama (v1 tercemar entry hasil intercept SW lama).
  e.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))),
      )
      .then(() => clients.claim()),
  );
});

self.addEventListener("fetch", (e) => {
  const req = e.request;

  // Jangan pernah intercept non-GET: POST Livewire/CSRF butuh pass-through
  // langsung ke jaringan (Cache API bahkan melempar TypeError untuk non-GET).
  // /livewire/* dan endpoint auth juga dikecualikan dari cache.
  if (req.method !== "GET" || new URL(req.url).pathname.startsWith("/livewire")) {
    return;
  }

  if (req.mode === "navigate") {
    e.respondWith(
      fetch(req).catch(() => caches.match("/offline")),
    );
    return;
  }

  e.respondWith(caches.match(req).then((r) => r || fetch(req)));
});
