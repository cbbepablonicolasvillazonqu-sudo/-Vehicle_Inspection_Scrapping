/**
 * Service Worker de Forte Towing (PWA).
 *
 * Estrategia:
 *  - Navegaciones: red primero; si no hay conexión → página /offline.
 *    (No se cachean páginas autenticadas por privacidad.)
 *  - Assets estáticos (build de Vite, iconos, manifiestos):
 *    caché primero con actualización en segundo plano.
 *  - Nunca intercepta /livewire (peticiones dinámicas y subidas).
 *  - Nunca guarda los archivos de los usuarios (/archivos, y /storage de
 *    antes): son privados y en un celular compartido seguirían ahí después
 *    de cerrar sesión.
 *
 * La v4 existe para borrar la v3, que guardaba fotos de /storage: al
 * activarse, este SW elimina toda caché con otro nombre.
 */
const CACHE = 'forte-towing-v4';

const PRECACHE = [
    '/offline',
    '/manifest.webmanifest',
    '/manifest.en.webmanifest',
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

    // Archivos de los usuarios: directo a la red, sin caché. Va antes que la
    // regla de assets porque una foto pedida por <img> es de tipo "image" y
    // si no la guardaría igual. El navegador ya la revalida con el servidor.
    if (url.pathname.startsWith('/archivos/') || url.pathname.startsWith('/storage/')) {
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
        || url.pathname.endsWith('.webmanifest');

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
