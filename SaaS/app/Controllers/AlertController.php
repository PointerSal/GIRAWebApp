<?php
// ============================================================
//  GIRA · app/Controllers/AlertController.php
//  Gestisce: lista alert attivi, storico, presa in carico,
//            chiusura manuale
// ============================================================

class AlertController
{
    // ----------------------------------------------------------
    //  GET /alert
    //  Lista alert aperti filtrata per ruolo
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediLogin();
        Middleware::verificaPasswordAggiornata();

        $db      = Database::getInstance();
        $ruolo   = Auth::ruolo();
        $id_utente = Auth::id();

        if ($ruolo === RUOLO_SUPERADMIN) {
            // Tutti gli alert aperti
            $stmt = $db->query(
                "SELECT a.*, d.label, d.mac, d.id_struttura,
                        s.ragione_sociale AS struttura,
                        u.area, u.subarea,
                        TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti,
                        CONCAT(ug.nome, ' ', ug.cognome) AS gestore_nome
                   FROM gir_alert a
                   JOIN gir_device d     ON d.id = a.id_device
                   JOIN gir_struttura s  ON s.id = d.id_struttura
              LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
              LEFT JOIN gir_utenti ug    ON ug.id = a.id_utente_gestore
                  WHERE a.chiuso_alle IS NULL
                  ORDER BY FIELD(a.tipo,'PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'),
                           a.aperto_alle ASC"
            );
            $alert = $stmt->fetchAll();
        } elseif (in_array($ruolo, [RUOLO_ADMIN, RUOLO_MEDICO])) {
            // Alert delle strutture accessibili
            $id_attiva = Auth::struttura_attiva();
            $strutture_ids = $id_attiva ? [$id_attiva] : Auth::strutture_accessibili();
            if (empty($strutture_ids)) {
                $alert = [];
            } else {
                $ph   = implode(',', array_fill(0, count($strutture_ids), '?'));
                $stmt = $db->prepare(
                    "SELECT a.*, d.label, d.mac, d.id_struttura,
                            s.ragione_sociale AS struttura,
                            u.area, u.subarea,
                            TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti,
                            CONCAT(ug.nome, ' ', ug.cognome) AS gestore_nome
                       FROM gir_alert a
                       JOIN gir_device d     ON d.id = a.id_device
                       JOIN gir_struttura s  ON s.id = d.id_struttura
                  LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
                  LEFT JOIN gir_utenti ug    ON ug.id = a.id_utente_gestore
                      WHERE a.chiuso_alle IS NULL
                        AND d.id_struttura IN ($ph)
                      ORDER BY FIELD(a.tipo,'PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'),
                               a.aperto_alle ASC"
                );
                $stmt->execute($strutture_ids);
                $alert = $stmt->fetchAll();
            }
        } else {
            // Operatore — solo i device assegnati
            $stmt = $db->prepare(
                "SELECT a.*, d.label, d.mac, d.id_struttura,
                        s.ragione_sociale AS struttura,
                        u.area, u.subarea,
                        TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti,
                        CONCAT(ug.nome, ' ', ug.cognome) AS gestore_nome
                   FROM gir_alert a
                   JOIN gir_device d      ON d.id = a.id_device
                   JOIN gir_struttura s   ON s.id = d.id_struttura
                   JOIN gir_utente_device ud ON ud.id_device = d.id AND ud.id_utente = :uid
              LEFT JOIN gir_ubicazione u  ON u.id = d.id_ubicazione
              LEFT JOIN gir_utenti ug     ON ug.id = a.id_utente_gestore
                  WHERE a.chiuso_alle IS NULL
                  ORDER BY FIELD(a.tipo,'PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'),
                           a.aperto_alle ASC"
            );
            $stmt->execute([':uid' => $id_utente]);
            $alert = $stmt->fetchAll();
        }

        $page_title   = 'Alert — GIRA';
        $current_page = 'alert';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'alert/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /alert/storico
    // ----------------------------------------------------------
    public static function storico(): void
    {
        Middleware::richiediClinico();
        Middleware::verificaPasswordAggiornata();

        $db = Database::getInstance();

        // Filtri
        $filtro_tipo      = $_GET['tipo']         ?? 'tutti';
        $filtro_struttura = (int)($_GET['id_struttura'] ?? 0);
        $pagina           = max(1, (int)($_GET['p'] ?? 1));
        $per_pagina       = 20;
        $offset           = ($pagina - 1) * $per_pagina;

        $id_attiva = Auth::struttura_attiva();
        $strutture_ids = $id_attiva ? [$id_attiva] : Auth::strutture_accessibili();
        if (empty($strutture_ids)) {
            $alert  = [];
            $totale = 0;
        } else {
            $ph     = implode(',', array_fill(0, count($strutture_ids), '?'));
            $params = $strutture_ids;

            $where_extra = '';
            if ($filtro_tipo !== 'tutti') {
                $where_extra .= " AND a.tipo = ?";
                $params[] = strtoupper($filtro_tipo);
            }
            if ($filtro_struttura) {
                $where_extra .= " AND d.id_struttura = ?";
                $params[] = $filtro_struttura;
            }

            // Conteggio totale
            $count_stmt = $db->prepare(
                "SELECT COUNT(*) FROM gir_alert a
                   JOIN gir_device d ON d.id = a.id_device
                  WHERE d.id_struttura IN ($ph)
                    AND a.chiuso_alle IS NOT NULL
                    $where_extra"
            );
            $count_stmt->execute($params);
            $totale = (int)$count_stmt->fetchColumn();

            // Query principale
            $params[] = $per_pagina;
            $params[] = $offset;
            $stmt = $db->prepare(
                "SELECT a.*, d.label, d.mac,
                        s.ragione_sociale AS struttura,
                        u.area, u.subarea,
                        TIMESTAMPDIFF(MINUTE, a.aperto_alle, a.chiuso_alle) AS durata_totale,
                        CONCAT(ug.nome, ' ', ug.cognome) AS gestore_nome
                   FROM gir_alert a
                   JOIN gir_device d     ON d.id = a.id_device
                   JOIN gir_struttura s  ON s.id = d.id_struttura
              LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
              LEFT JOIN gir_utenti ug    ON ug.id = a.id_utente_gestore
                  WHERE d.id_struttura IN ($ph)
                    AND a.chiuso_alle IS NOT NULL
                    $where_extra
                  ORDER BY a.aperto_alle DESC
                  LIMIT ? OFFSET ?"
            );
            $stmt->execute($params);
            $alert = $stmt->fetchAll();
        }

        $tot_pagine = $totale > 0 ? (int)ceil($totale / $per_pagina) : 1;

        // Strutture per filtro
        $strutture_map = [];
        if (!empty($strutture_ids)) {
            $ph2  = implode(',', array_fill(0, count($strutture_ids), '?'));
            $rows = $db->prepare(
                "SELECT id, ragione_sociale FROM gir_struttura WHERE id IN ($ph2) ORDER BY ragione_sociale"
            );
            $rows->execute($strutture_ids);
            foreach ($rows->fetchAll() as $r) {
                $strutture_map[$r['id']] = $r['ragione_sociale'];
            }
        }

        $page_title   = 'Storico alert — GIRA';
        $current_page = 'alert-storico';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'alert/storico.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /alert/prendi-in-carico/{id}
    // ----------------------------------------------------------
    public static function prendiInCarico(?int $id): void
    {
        Middleware::richiediNonMedico();

        $alert = self::_trova($id);
        self::_verifica_accesso_alert($alert);

        // Segna come gestito
        Database::getInstance()->prepare(
            'UPDATE gir_alert
                SET gestito           = 1,
                    id_utente_gestore = :uid
              WHERE id = :id AND gestito = 0'
        )->execute([':uid' => Auth::id(), ':id' => $id]);

        $_SESSION['successo'] = 'Alert preso in carico.';
        header('Location: ' . APP_URL . '/alert');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /alert/chiudi/{id}
    //  Mostra form con nota opzionale
    //  Non consentito per ROSSO e ARANCIO — chiusi solo da ingest
    // ----------------------------------------------------------
    public static function chiudi(?int $id): void
    {
        Middleware::richiediNonMedico();

        $alert = self::_trova($id);
        self::_verifica_accesso_alert($alert);

        // ROSSO e ARANCIO si chiudono solo automaticamente
        if (in_array($alert['tipo'], ['ROSSO', 'ARANCIO'])) {
            $_SESSION['errore'] = 'Gli alert di immobilità si chiudono automaticamente quando il paziente cambia posizione.';
            header('Location: ' . APP_URL . '/alert');
            exit;
        }

        $errore = $_SESSION['errore'] ?? null;
        unset($_SESSION['errore']);

        $page_title   = 'Chiudi alert — GIRA';
        $current_page = 'alert';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'alert/chiudi.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /alert/chiudi-post
    // ----------------------------------------------------------
    public static function chiudiPost(): void
    {
        Middleware::richiediNonMedico();

        $id    = (int)($_POST['id'] ?? 0);
        $note  = trim($_POST['note'] ?? '') ?: null;
        $alert = self::_trova($id);
        self::_verifica_accesso_alert($alert);

        // ROSSO e ARANCIO si chiudono solo automaticamente
        if (in_array($alert['tipo'], ['ROSSO', 'ARANCIO'])) {
            $_SESSION['errore'] = 'Gli alert di immobilità si chiudono automaticamente quando il paziente cambia posizione.';
            header('Location: ' . APP_URL . '/alert');
            exit;
        }

        Database::getInstance()->prepare(
            'UPDATE gir_alert
                SET chiuso_alle       = NOW(),
                    gestito           = 1,
                    id_utente_gestore = :uid,
                    note              = :note
              WHERE id = :id AND chiuso_alle IS NULL'
        )->execute([
            ':uid'  => Auth::id(),
            ':note' => $note,
            ':id'   => $id,
        ]);

        $_SESSION['successo'] = 'Alert chiuso.';
        header('Location: ' . APP_URL . '/alert');
        exit;
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    private static function _trova(?int $id): array
    {
        if (!$id) {
            $_SESSION['errore'] = 'ID non valido.';
            header('Location: ' . APP_URL . '/alert');
            exit;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT a.*, d.id_struttura
               FROM gir_alert a
               JOIN gir_device d ON d.id = a.id_device
              WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $alert = $stmt->fetch();
        if (!$alert) {
            $_SESSION['errore'] = 'Alert non trovato.';
            header('Location: ' . APP_URL . '/alert');
            exit;
        }
        return $alert;
    }

    private static function _verifica_accesso_alert(array $alert): void
    {
        if (Auth::isSuperadmin()) return;
        if (!Auth::puo_accedere_struttura((int)$alert['id_struttura'])) {
            $_SESSION['errore'] = 'Non hai accesso a questo alert.';
            header('Location: ' . APP_URL . '/alert');
            exit;
        }
    }
}
