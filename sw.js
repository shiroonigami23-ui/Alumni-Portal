const CACHE_NAME = "rjit-portal-v1";
const APP_SCOPE = "/alumni_portal/";
const PRE_CACHE = [
  APP_SCOPE,
  APP_SCOPE + "feed.php",
  APP_SCOPE + "offline.html",
  APP_SCOPE + "assets/css/variety-ui.css",
  APP_SCOPE + "assets/js/variety-ui.js",
  APP_SCOPE + "assets/js/pwa.js",
  APP_SCOPE + "assets/icons/app-icon-192.png",
  APP_SCOPE + "assets/icons/app-icon-512.png"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRE_CACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") return;
  if (req.url.includes("/api/")) return;

  event.respondWith(
    fetch(req)
      .then((response) => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(req, copy)).catch(() => {});
        return response;
      })
      .catch(() => caches.match(req).then((cached) => cached || caches.match(APP_SCOPE + "offline.html")))
  );
});

self.addEventListener("push", (event) => {
  let payload = { title: "RJIT Alumni Portal", body: "You have a new notification." };
  try {
    payload = event.data ? event.data.json() : payload;
  } catch (_e) {}
  event.waitUntil(
    self.registration.showNotification(payload.title || "RJIT Alumni Portal", {
      body: payload.body || "You have a new notification.",
      icon: APP_SCOPE + "assets/icons/app-icon-192.png",
      badge: APP_SCOPE + "assets/icons/app-icon-192.png",
      data: { url: payload.url || APP_SCOPE + "feed.php" }
    })
  );
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url) || APP_SCOPE + "feed.php";
  event.waitUntil(
    clients.matchAll({ type: "window", includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if (client.url.includes(targetUrl) && "focus" in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) return clients.openWindow(targetUrl);
      return null;
    })
  );
});

