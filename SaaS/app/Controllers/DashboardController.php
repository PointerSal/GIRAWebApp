<?php
// ============================================================
//  GIRA · app/Controllers/DashboardController.php
//  Smista alla view corretta in base al ruolo
// ============================================================

class DashboardController
{
    public static function index(): void
    {
        Middleware::richiediLogin();
        Middleware::verificaPasswordAggiornata();

        $db     = Database::getInstance();
        $utente = Auth::utente();
        $ruolo  = Auth::ruolo();

        $page_title   = 'Dashboard — GIRA';
        $current_page = 'dashboard';

        if ($ruolo === RUOLO_SUPERADMIN) {
            self::_superadmin($db, $utente, $page_title, $current_page);
        } elseif ($ruolo === RUOLO_ADMIN) {
            self::_admin($db, $utente, $page_title, $current_page);
        } elseif ($ruolo === RUOLO_MEDICO) {
            self::_medico($db, $utente, $page_title, $current_page);
        } else {
            self::_operatore($db, $utente, $page_title, $current_page);
        }
    }

    // ----------------------------------------------------------
    //  SUPERADMIN — visione globale piattaforma
    // ----------------------------------------------------------
    private static function _superadmin(PDO $db, array $utente, string $page_title, string $current_page): void
    {
        // Statistiche globali
        $tot_strutture = $db->query('SELECT COUNT(*) FROM gir_struttura WHERE attiva = 1')->fetchColumn();
        $tot_utenti    = $db->query('SELECT COUNT(*) FROM gir_utenti    WHERE attivo = 1')->fetchColumn();
        $tot_device    = $db->query('SELECT COUNT(*) FROM gir_device    WHERE attivo = 1')->fetchColumn();
        $tot_alert     = $db->query('SELECT COUNT(*) FROM gir_alert     WHERE chiuso_alle IS NULL')->fetchColumn();

        // Strutture attive con contatori
        $strutture = $db->query(
            'SELECT s.*,
                    COUNT(DISTINCT d.id)  AS tot_device,
                    COUNT(DISTINCT u.id)  AS tot_utenti,
                    COUNT(DISTINCT a.id)  AS alert_aperti
               FROM gir_struttura s
          LEFT JOIN gir_device d  ON d.id_struttura = s.id AND d.attivo = 1
          LEFT JOIN gir_utenti u  ON u.id IN (
                                      SELECT id_utente FROM gir_utente_struttura
                                       WHERE id_struttura = s.id
                                    ) AND u.attivo = 1
          LEFT JOIN gir_alert a   ON a.id_device IN (
                                      SELECT id FROM gir_device WHERE id_struttura = s.id
                                    ) AND a.chiuso_alle IS NULL
              WHERE s.attiva = 1
              GROUP BY s.id
              ORDER BY s.ragione_sociale'
        )->fetchAll();

        // Alert rossi aperti (urgenti)
        $alert_rossi = $db->query(
            'SELECT a.*, d.mac, d.label,
                    s.ragione_sociale AS struttura,
                    TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti
               FROM gir_alert a
               JOIN gir_device d   ON d.id = a.id_device
               JOIN gir_struttura s ON s.id = d.id_struttura
              WHERE a.tipo = "ROSSO" AND a.chiuso_alle IS NULL
              ORDER BY a.aperto_alle ASC
              LIMIT 10'
        )->fetchAll();

        include VIEW_PATH . 'dashboard/superadmin.php';
    }

    // ----------------------------------------------------------
    //  ADMIN — visione della propria struttura
    // ----------------------------------------------------------
    private static function _admin(PDO $db, array $utente, string $page_title, string $current_page): void
    {
        //$strutture_ids = Auth::strutture_accessibili();
        $id_struttura  = Auth::struttura_attiva();
        $strutture_ids = $id_struttura ? [$id_struttura] : Auth::strutture_accessibili();
        if (empty($strutture_ids)) {
            include VIEW_PATH . 'dashboard/no_struttura.php';
            return;
        }

        // Usa la prima struttura (di solito l'admin ne ha una sola)
        //$id_struttura = $strutture_ids[0];

        $struttura = $db->prepare(
            'SELECT * FROM gir_struttura WHERE id = :id LIMIT 1'
        );
        $struttura->execute([':id' => $id_struttura]);
        $struttura = $struttura->fetch();

        // Contatori struttura
        $tot_device  = $db->prepare('SELECT COUNT(*) FROM gir_device WHERE id_struttura = :id AND attivo = 1');
        $tot_device->execute([':id' => $id_struttura]);
        $tot_device = $tot_device->fetchColumn();

        $tot_utenti = $db->prepare(
            'SELECT COUNT(*) FROM gir_utente_struttura us
               JOIN gir_utenti u ON u.id = us.id_utente
              WHERE us.id_struttura = :id AND u.attivo = 1'
        );
        $tot_utenti->execute([':id' => $id_struttura]);
        $tot_utenti = $tot_utenti->fetchColumn();

        // Alert aperti per tipo
        $alert_count = $db->prepare(
            'SELECT tipo, COUNT(*) AS n
               FROM gir_alert a
               JOIN gir_device d ON d.id = a.id_device
              WHERE d.id_struttura = :id AND a.chiuso_alle IS NULL
              GROUP BY tipo'
        );
        $alert_count->execute([':id' => $id_struttura]);
        $alert_per_tipo = array_column($alert_count->fetchAll(), 'n', 'tipo');

        // Stato device attivi
        $device_stato = $db->prepare(
            'SELECT d.id, d.label, d.mac,
                    u.area, u.subarea,
                    ds.posizione, ds.stato_batt, ds.stato_segnale,
                    ds.ultimo_contatto,
                    TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio,
                    a.tipo AS alert_tipo
               FROM gir_device d
          LEFT JOIN gir_ubicazione u  ON u.id = d.id_ubicazione
          LEFT JOIN gir_device_stato ds ON ds.id_device = d.id
          LEFT JOIN gir_alert a       ON a.id_device = d.id
                                     AND a.chiuso_alle IS NULL
                                     AND a.tipo IN ("ROSSO","ARANCIO")
              WHERE d.id_struttura = :id AND d.attivo = 1
              ORDER BY a.tipo DESC, d.label'
        );
        $device_stato->execute([':id' => $id_struttura]);
        $device_stato = $device_stato->fetchAll();

        include VIEW_PATH . 'dashboard/admin.php';
    }

    // ----------------------------------------------------------
    //  MEDICO — visione clinica multi-struttura
    // ----------------------------------------------------------
    private static function _medico(PDO $db, array $utente, string $page_title, string $current_page): void
    {
        //$strutture_ids = Auth::strutture_accessibili();
        $id_struttura  = Auth::struttura_attiva();
        $strutture_ids = $id_struttura ? [$id_struttura] : Auth::strutture_accessibili();

        // Alert aperti nelle strutture del medico
        $placeholders = implode(',', array_fill(0, count($strutture_ids), '?'));
        $alert_aperti = $db->prepare(
            "SELECT a.*, d.label, d.mac,
                    s.ragione_sociale AS struttura,
                    u.area, u.subarea,
                    TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti
               FROM gir_alert a
               JOIN gir_device d    ON d.id = a.id_device
               JOIN gir_struttura s ON s.id = d.id_struttura
          LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
              WHERE d.id_struttura IN ($placeholders)
                AND a.chiuso_alle IS NULL
              ORDER BY FIELD(a.tipo,'ROSSO','PULSANTE','ARANCIO','BATTERIA','OFFLINE'),
                       a.aperto_alle ASC"
        );
        $alert_aperti->execute($strutture_ids);
        $alert_aperti = $alert_aperti->fetchAll();

        // Storico posizioni recente (ultime 4h)
        $storico = $db->prepare(
            "SELECT pl.*, d.label, s.ragione_sociale AS struttura
               FROM gir_posizione_log pl
               JOIN gir_device d    ON d.id = pl.id_device
               JOIN gir_struttura s ON s.id = d.id_struttura
              WHERE d.id_struttura IN ($placeholders)
                AND pl.iniziato_alle >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
              ORDER BY pl.iniziato_alle DESC
              LIMIT 50"
        );
        $storico->execute($strutture_ids);
        $storico = $storico->fetchAll();

        include VIEW_PATH . 'dashboard/medico.php';
    }

    // ----------------------------------------------------------
    //  OPERATORE — solo i propri device e alert
    // ----------------------------------------------------------
    private static function _operatore(PDO $db, array $utente, string $page_title, string $current_page): void
    {
        $id_utente = Auth::id();
        $id_struttura = Auth::struttura_attiva();
        // Device assegnati a questo operatore
        $device_assegnati = $db->prepare(
            'SELECT d.id, d.label, d.mac,
                    u.area, u.subarea,
                    ds.posizione, ds.stato_batt, ds.stato_segnale,
                    ds.ultimo_contatto,
                    TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio,
                    a.id AS alert_id, a.tipo AS alert_tipo,
                    TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS alert_minuti
               FROM gir_utente_device ud
               JOIN gir_device d          ON d.id = ud.id_device AND d.attivo = 1
          LEFT JOIN gir_ubicazione u       ON u.id = d.id_ubicazione
          LEFT JOIN gir_device_stato ds    ON ds.id_device = d.id
          LEFT JOIN gir_alert a            ON a.id_device = d.id
                                          AND a.chiuso_alle IS NULL
                                          AND a.tipo IN ("ROSSO","ARANCIO","PULSANTE")
              WHERE ud.id_utente = :uid AND (:id_struttura1 = 0 OR d.id_struttura = :id_struttura2)
              ORDER BY FIELD(a.tipo,"PULSANTE","ROSSO","ARANCIO"), d.label'
        );
        $device_assegnati->execute([
            ':uid'           => $id_utente,
            ':id_struttura1' => $id_struttura,
            ':id_struttura2' => $id_struttura,
        ]);
        $device_assegnati = $device_assegnati->fetchAll();

        include VIEW_PATH . 'dashboard/operatore.php';
    }
}
