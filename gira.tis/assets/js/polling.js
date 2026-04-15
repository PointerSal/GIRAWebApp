// ============================================================
//  GIRA · assets/js/polling.js
//  Polling AJAX silenzioso per aggiornamento dati real-time
//
//  Uso nelle view:
//  GiraPolling.avvia('alert');     // per pagina alert
//  GiraPolling.avvia('dashboard'); // per dashboard
// ============================================================

const GiraPolling = (() => {

    let _timer    = null;
    let _modalita = null;
    const _intervallo = (typeof POLLING_INTERVAL !== 'undefined') ? POLLING_INTERVAL : 10000;

    // ----------------------------------------------------------
    //  API pubblica
    // ----------------------------------------------------------
    function avvia(modalita) {
        _modalita = modalita;
        _ciclo();
        _timer = setInterval(_ciclo, _intervallo);

        // Pausa quando tab non visibile
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(_timer);
            } else {
                _ciclo();
                _timer = setInterval(_ciclo, _intervallo);
            }
        });
    }

    function ferma() {
        clearInterval(_timer);
    }

    // ----------------------------------------------------------
    //  Ciclo principale
    // ----------------------------------------------------------
    function _ciclo() {
        switch (_modalita) {
            case 'alert':
                _aggiornaAlert();
                break;
            case 'dashboard-operatore':
                _aggiornaDeviceOperatore();
                break;
            case 'dashboard-admin':
                _aggiornaContatori();
                _aggiornaDeviceAdmin();
                break;
            case 'dashboard-superadmin':
                _aggiornaContatoriSuperadmin();
                break;
        }
    }

    // ----------------------------------------------------------
    //  Aggiorna pagina alert
    // ----------------------------------------------------------
    function _aggiornaAlert() {
        fetch('/api/alert-attivi')
            .then(r => { if (r.status === 401) { location.reload(); } return r.json(); })
            .then(alert => {
                const container = document.getElementById('gira-alert-list');
                if (!container) return;

                // Aggiorna contatore header
                const counter = document.getElementById('gira-alert-count');
                if (counter) counter.textContent = alert.length + ' alert apert' + (alert.length === 1 ? 'o' : 'i');

                // Aggiorna ogni riga esistente o aggiungi nuove
                const ids_ricevuti = alert.map(a => a.id);
                const righe_esistenti = container.querySelectorAll('[data-alert-id]');

                // Rimuovi righe non più presenti
                righe_esistenti.forEach(riga => {
                    if (!ids_ricevuti.includes(parseInt(riga.dataset.alertId))) {
                        riga.remove();
                    }
                });

                // Aggiorna minuti per ogni riga esistente
                alert.forEach(a => {
                    const riga = container.querySelector('[data-alert-id="' + a.id + '"]');
                    if (riga) {
                        const minutiEl = riga.querySelector('.gira-minuti');
                        if (minutiEl) minutiEl.textContent = a.minuti_aperti + ' min';
                    }
                });

                // Aggiorna contatori per tipo
                _aggiornaContatoriTipo(alert);
            })
            .catch(() => {}); // silenzioso
    }

    // ----------------------------------------------------------
    //  Aggiorna device dashboard operatore
    // ----------------------------------------------------------
    function _aggiornaDeviceOperatore() {
        fetch('/api/device-stati')
            .then(r => { if (r.status === 401) { location.reload(); } return r.json(); })
            .then(device => {
                device.forEach(d => {
                    const riga = document.querySelector('[data-device-id="' + d.id + '"]');
                    if (!riga) return;

                    // Aggiorna pill stato
                    const pill = riga.querySelector('.gira-stato-pill');
                    if (pill) {
                        const offline = d.ultimo_contatto === null || (d.minuti_silenzio ?? 999) > 10;
                        if (d.alert_tipo === 'PULSANTE') {
                            pill.className = 'pill pill--red gira-stato-pill';
                            pill.textContent = '🆘 SOS';
                        } else if (d.alert_tipo === 'ROSSO') {
                            pill.className = 'pill pill--red gira-stato-pill';
                            pill.textContent = 'Rosso';
                        } else if (d.alert_tipo === 'ARANCIO') {
                            pill.className = 'pill pill--warn gira-stato-pill';
                            pill.textContent = 'Arancio';
                        } else if (offline) {
                            pill.className = 'pill pill--muted gira-stato-pill';
                            pill.textContent = 'Offline';
                        } else {
                            pill.className = 'pill pill--ok gira-stato-pill';
                            pill.textContent = 'OK';
                        }
                    }

                    // Aggiorna posizione
                    const posEl = riga.querySelector('.gira-posizione');
                    if (posEl && d.posizione) posEl.textContent = d.posizione;

                    // Aggiorna batteria
                    const battEl = riga.querySelector('.gira-batteria');
                    if (battEl && d.stato_batt !== null && d.stato_batt > 0) {
                        battEl.textContent = '🔋 ' + d.stato_batt + '%';
                        battEl.style.color = d.stato_batt < 20 ? 'var(--amber)' : 'var(--muted)';
                    }
                });
            })
            .catch(() => {});
    }

    // ----------------------------------------------------------
    //  Aggiorna device dashboard admin
    // ----------------------------------------------------------
    function _aggiornaDeviceAdmin() {
        _aggiornaDeviceOperatore(); // stessa logica
    }

    // ----------------------------------------------------------
    //  Aggiorna contatori dashboard admin/medico
    // ----------------------------------------------------------
    function _aggiornaContatori() {
        fetch('/api/contatori')
            .then(r => r.json())
            .then(data => {
                _setContatore('gira-count-rossi',   data.alert_rossi,   data.alert_rossi > 0   ? 'var(--red)'   : 'var(--green)');
                _setContatore('gira-count-arancio', data.alert_arancio, data.alert_arancio > 0 ? 'var(--amber)' : 'var(--green)');
            })
            .catch(() => {});
    }

    // ----------------------------------------------------------
    //  Aggiorna contatori dashboard superadmin
    // ----------------------------------------------------------
    function _aggiornaContatoriSuperadmin() {
        fetch('/api/contatori')
            .then(r => r.json())
            .then(data => {
                _setContatore('gira-count-strutture', data.strutture);
                _setContatore('gira-count-utenti',    data.utenti);
                _setContatore('gira-count-device',    data.device);
                _setContatore('gira-count-alert',     data.alert, data.alert > 0 ? 'var(--red)' : 'var(--green)');
            })
            .catch(() => {});
    }

    // ----------------------------------------------------------
    //  Aggiorna contatori per tipo nella pagina alert
    // ----------------------------------------------------------
    function _aggiornaContatoriTipo(alert) {
        const per_tipo = {};
        alert.forEach(a => { per_tipo[a.tipo] = (per_tipo[a.tipo] || 0) + 1; });

        ['PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'].forEach(tipo => {
            const el = document.getElementById('gira-count-tipo-' + tipo.toLowerCase());
            if (el) el.textContent = per_tipo[tipo] || 0;
        });
    }

    // ----------------------------------------------------------
    //  Helper — imposta valore e colore di un contatore
    // ----------------------------------------------------------
    function _setContatore(id, valore, colore) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = valore;
        if (colore) el.style.color = colore;
    }

    return { avvia, ferma };

})();
