// GIRA — Service Worker
const VERSION = 'gira-v5';

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

// ── Push notification ─────────────────────────────────────
self.addEventListener('push', e => {
    if (!e.data) return;

    const data = e.data.json();

    const title   = data.title   ?? 'GIRA Alert';
    const options = {
        body:    data.body    ?? '',
        icon:    data.icon    ?? '/assets/img/gira_192x192.png',
        badge:   data.badge   ?? '/assets/img/icon_72x72.png',
        tag:     data.tag     ?? 'gira-alert',
        data:    { url: data.url ?? '/' },
        requireInteraction: data.requireInteraction ?? false,
    };

    e.waitUntil(self.registration.showNotification(title, options));
});

// Click sulla notifica → apre/focusa la pagina
self.addEventListener('notificationclick', e => {
    e.notification.close();
    const url = e.notification.data?.url ?? '/';

    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const client of list) {
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});