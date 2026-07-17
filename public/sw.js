/**
 * Service Worker de Forte Towing (PWA).
 *
 * Estrategia:
 *  - Navegaciones: red primero; si no hay conexión → página /offline.
 *    (No se cachean páginas autenticadas por privacidad.)
 *  - Assets estáticos (build de Vite, iconos, fotos de /storage):
 *    caché primero con actualización en segundo plano.
 *  - Nunca intercepta /livewire (peticiones dinámicas y subidas).
 */
const CACHE = 'forte-towing-v1';

const PRECACHE = [
    '/offline',
    '/manifest.webmanifest',
    '/iconos/icono-192.png',
    '/iconos/icono-512.png',
];

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys()
            .then((claves) => Promise.all(claves.filter((c) => c !== CACHE).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (evento) => {
    const peticion = evento.request;
    const url = new URL(peticion.url);

    // Solo GET del mismo origen; nunca Livewire.
    if (peticion.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/livewire')) {
        return;
    }

    // Navegaciones: red primero, respaldo offline.
    if (peticion.mode === 'navigate') {
        evento.respondWith(
            fetch(peticion).catch(() =>
                caches.match(peticion).then((respuesta) => respuesta || caches.match('/offline'))
            )
        );
        return;
    }

    // Assets estáticos: caché primero + actualización en segundo plano.
    const esAsset = ['style', 'script', 'image', 'font'].includes(peticion.destination)
        || url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/iconos/')
        || url.pathname.startsWith('/storage/')
        || url.pathname === '/manifest.webmanifest';

    if (esAsset) {
        evento.respondWith(
            caches.match(peticion).then((enCache) => {
                const actualizacion = fetch(peticion)
                    .then((respuesta) => {
                        if (respuesta && respuesta.ok) {
                            const copia = respuesta.clone();
                            caches.open(CACHE).then((cache) => cache.put(peticion, copia));
                        }
                        return respuesta;
                    })
                    .catch(() => enCache);

                return enCache || actualizacion;
            })
        );
    }
});
