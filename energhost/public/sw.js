// Service Worker de Energhost.
// Guarda en caché los archivos estáticos para que la app se abra sin internet.
// Las respuestas de la API NUNCA se guardan: siempre tienen que ser datos actuales.
// Cambiar VERSION cada vez que se modifiquen los archivos estáticos.

const VERSION = "energhost-v3";
const ARCHIVOS = [
  "./css/app.css",
  "./css/landing.css",
  "./js/dispositivo.js",
  "./js/panel.js",
  "./js/landing.js",
  "./manifest.json",
  "./favicon.ico",
  "./",
];

self.addEventListener("install", (evento) => {
  evento.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(ARCHIVOS)));
  self.skipWaiting();
});

self.addEventListener("activate", (evento) => {
  evento.waitUntil(
    caches.keys().then((claves) =>
      Promise.all(claves.filter((clave) => clave !== VERSION).map((clave) => caches.delete(clave)))
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", (evento) => {
  const url = new URL(evento.request.url);

  // La API y los formularios van siempre a la red
  if (evento.request.method !== "GET" || url.pathname.includes("/api/")) {
    return;
  }

  // Archivos estáticos: primero caché, si no está, red
  if (ARCHIVOS.some((archivo) => archivo !== "./" && url.pathname.endsWith(archivo.replace("./", "/")))) {
    evento.respondWith(caches.match(evento.request).then((guardado) => guardado || fetch(evento.request)));
    return;
  }

  // Páginas: siempre desde la red (pueden tener datos personales, no se guardan).
  // Sin internet se muestra la presentación guardada.
  if (evento.request.mode === "navigate") {
    evento.respondWith(fetch(evento.request).catch(() => caches.match("./")));
  }
});
