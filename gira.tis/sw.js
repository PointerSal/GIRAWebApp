// GIRA — Service Worker minimale
// Serve solo per soddisfare il requisito PWA installabile.
// Nessuna cache offline: tutto passa sempre dal server.

const VERSION = 'gira-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(self.clients.claim());
});

// Strategia: network-first, nessuna cache
self.addEventListener('fetch', e => {
    e.respondWith(
        fetch(e.request).catch(() => {
            // ignora errori di rete silenziosamente
        })
    );
});
