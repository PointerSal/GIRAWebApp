// ============================================================
//  GIRA · assets/js/push.js
//  Registra il service worker e la push subscription.
//  Incluso in footer.php dopo il login.
// ============================================================

(function () {
    'use strict';

    // Chiave pubblica VAPID — iniettata dal PHP nel footer
    // window.GIRA_VAPID_PUBLIC deve essere definita prima di questo script
    const VAPID_PUBLIC = window.GIRA_VAPID_PUBLIC ?? null;
    const SUBSCRIBE_URL   = (window.APP_URL ?? '') + '/push/subscribe';
    const UNSUBSCRIBE_URL = (window.APP_URL ?? '') + '/push/unsubscribe';

    if (!VAPID_PUBLIC) {
        console.warn('GIRA Push: VAPID_PUBLIC non definita');
        return;
    }

    // Verifica supporto browser
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.info('GIRA Push: browser non supporta push notifications');
        return;
    }

    // ── Utility: converte base64url → Uint8Array (per VAPID) ──
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw     = atob(base64);
        return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
    }

    // ── Invia subscription al server ──────────────────────────
    async function sendSubscription(subscription, url) {
        try {
            await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(subscription),
            });
        } catch (e) {
            console.warn('GIRA Push: errore invio subscription', e);
        }
    }

    // ── Registra SW e chiedi permesso push ────────────────────
    async function inizializza() {
        try {
            const reg = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            // Se l'utente ha già negato il permesso → stop
            if (Notification.permission === 'denied') return;

            // Controlla se esiste già una subscription attiva
            let sub = await reg.pushManager.getSubscription();

            if (!sub) {
                // Chiedi permesso e crea subscription
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') return;

                sub = await reg.pushManager.subscribe({
                    userVisibleOnly:      true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC),
                });

                await sendSubscription(sub, SUBSCRIBE_URL);

            } else if (window.GIRA_FORZA_PUSH_SYNC === true) {
                // Re-invia la subscription esistente al server per aggiornare id_utente
                // (es. dopo logout + login con account diverso sullo stesso browser)
                await sendSubscription(sub, SUBSCRIBE_URL);
            }

            // Espone funzione globale per de-registrarsi (usata da preferenze.php)
            window.GiraPush = {
                async disattiva() {
                    if (!sub) return;
                    await sendSubscription({ endpoint: sub.endpoint }, UNSUBSCRIBE_URL);
                    await sub.unsubscribe();
                    sub = null;
                },
                async attiva() {
                    if (sub) return; // già attiva
                    sub = await reg.pushManager.subscribe({
                        userVisibleOnly:      true,
                        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC),
                    });
                    await sendSubscription(sub, SUBSCRIBE_URL);
                },
                isAttiva() {
                    return !!sub;
                }
            };

        } catch (e) {
            console.warn('GIRA Push: errore inizializzazione', e);
        }
    }

    // Avvia dopo il caricamento della pagina
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inizializza);
    } else {
        inizializza();
    }

})();
