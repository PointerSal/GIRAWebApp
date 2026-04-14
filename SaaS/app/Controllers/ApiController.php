<?php
// ============================================================
//  GIRA · app/Controllers/ApiController.php
//  Endpoint JSON leggeri per il polling AJAX
//  Richiedono login — rispondono solo JSON
// ============================================================

class ApiController
{
    // ----------------------------------------------------------
    //  GET /api/alert-attivi
    //  Restituisce gli alert aperti filtrati per ruolo
    // ----------------------------------------------------------
    public static function alertAttivi(): void
    {
        self::_richiediLoginJson();

        $db        = Database::getInstance();
        $ruolo     = Auth::ruolo();
        $id_utente = Auth::id();
        $id_struttura = Auth::struttura_attiva();

        if ($ruolo === RUOLO_SUPERADMIN) {
            $stmt = $db->query(
                "SELECT a.id, a.tipo, a.gestito, a.aperto_alle,
                        d.label, d.mac,
                        s.ragione_sociale AS struttura,
                        u.area, u.subarea,
                        TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti
                   FROM gir_alert a
                   JOIN gir_device d     ON d.id = a.id_device
                   JOIN gir_struttura s  ON s.id = d.id_struttura
              LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
                  WHERE a.chiuso_alle IS NULL
                  ORDER BY FIELD(a.tipo,'PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'),
                           a.aperto_alle ASC"
            );
            $alert = $stmt->fetchAll();

        } elseif (in_array($ruolo, [RUOLO_ADMIN, RUOLO_MEDICO])) {
            $strutture_ids = $id_struttura ? [$id_struttura] : Auth::strutture_accessibili();
            if (empty($strutture_ids)) {
                self::_json([]);
            }
            $ph   = implode(',', array_fill(0, count($strutture_ids), '?'));
            $stmt = $db->prepare(
                "SELECT a.id, a.tipo, a.gestito, a.aperto_alle,
                        d.label, d.mac,
                        s.ragione_sociale AS struttura,
                        u.area, u.subarea,
                        TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti
                   FROM gir_alert a
                   JOIN gir_device d     ON d.id = a.id_device
                   JOIN gir_struttura s  ON s.id = d.id_struttura
              LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
                  WHERE a.chiuso_alle IS NULL
                    AND d.id_struttura IN ($ph)
                  ORDER BY FIELD(a.tipo,'PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'),
                           a.aperto_alle ASC"
            );
            $stmt->execute($strutture_ids);
            $alert = $stmt->fetchAll();

        } else {
            // Operatore
            $stmt = $db->prepare(
                "SELECT a.id, a.tipo, a.gestito, a.aperto_alle,
                        d.label, d.mac,
                        s.ragione_sociale AS struttura,
                        u.area, u.subarea,
                        TIMESTAMPDIFF(MINUTE, a.aperto_alle, NOW()) AS minuti_aperti
                   FROM gir_alert a
                   JOIN gir_device d        ON d.id = a.id_device
                   JOIN gir_struttura s     ON s.id = d.id_struttura
                   JOIN gir_utente_device ud ON ud.id_device = d.id AND ud.id_utente = :uid
              LEFT JOIN gir_ubicazione u    ON u.id = d.id_ubicazione
                  WHERE a.chiuso_alle IS NULL
                    AND (:id_struttura1 = 0 OR d.id_struttura = :id_struttura2)
                  ORDER BY FIELD(a.tipo,'PULSANTE','ROSSO','ARANCIO','BATTERIA','OFFLINE'),
                           a.aperto_alle ASC"
            );
            $stmt->execute([
                ':uid'           => $id_utente,
                ':id_struttura1' => $id_struttura,
                ':id_struttura2' => $id_struttura,
            ]);
            $alert = $stmt->fetchAll();
        }

        self::_json($alert);
    }

    // ----------------------------------------------------------
    //  GET /api/device-stati
    //  Restituisce lo stato attuale dei device
    // ----------------------------------------------------------
    public static function deviceStati(): void
    {
        self::_richiediLoginJson();

        $db           = Database::getInstance();
        $ruolo        = Auth::ruolo();
        $id_utente    = Auth::id();
        $id_struttura = Auth::struttura_attiva();

        if ($ruolo === RUOLO_SUPERADMIN) {
            $stmt = $db->query(
                "SELECT d.id, d.label, d.mac,
                        ds.posizione, ds.stato_batt, ds.stato_segnale, ds.stato_pulsante,
                        ds.ultimo_contatto,
                        TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio,
                        a.tipo AS alert_tipo
                   FROM gir_device d
                   JOIN gir_device_stato ds ON ds.id_device = d.id
              LEFT JOIN gir_alert a         ON a.id_device = d.id
                                          AND a.chiuso_alle IS NULL
                                          AND a.tipo IN ('ROSSO','ARANCIO','PULSANTE')
                  WHERE d.attivo = 1
                  ORDER BY d.label"
            );
            $device = $stmt->fetchAll();

        } elseif (in_array($ruolo, [RUOLO_ADMIN, RUOLO_MEDICO])) {
            $strutture_ids = $id_struttura ? [$id_struttura] : Auth::strutture_accessibili();
            if (empty($strutture_ids)) self::_json([]);
            $ph   = implode(',', array_fill(0, count($strutture_ids), '?'));
            $stmt = $db->prepare(
                "SELECT d.id, d.label, d.mac,
                        ds.posizione, ds.stato_batt, ds.stato_segnale, ds.stato_pulsante,
                        ds.ultimo_contatto,
                        TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio,
                        a.tipo AS alert_tipo
                   FROM gir_device d
                   JOIN gir_device_stato ds ON ds.id_device = d.id
              LEFT JOIN gir_alert a         ON a.id_device = d.id
                                          AND a.chiuso_alle IS NULL
                                          AND a.tipo IN ('ROSSO','ARANCIO','PULSANTE')
                  WHERE d.attivo = 1 AND d.id_struttura IN ($ph)
                  ORDER BY d.label"
            );
            $stmt->execute($strutture_ids);
            $device = $stmt->fetchAll();

        } else {
            // Operatore — solo device assegnati
            $stmt = $db->prepare(
                "SELECT d.id, d.label, d.mac,
                        ds.posizione, ds.stato_batt, ds.stato_segnale, ds.stato_pulsante,
                        ds.ultimo_contatto,
                        TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio,
                        a.tipo AS alert_tipo
                   FROM gir_utente_device ud
                   JOIN gir_device d          ON d.id = ud.id_device AND d.attivo = 1
                   JOIN gir_device_stato ds   ON ds.id_device = d.id
              LEFT JOIN gir_alert a           ON a.id_device = d.id
                                           AND a.chiuso_alle IS NULL
                                           AND a.tipo IN ('ROSSO','ARANCIO','PULSANTE')
                  WHERE ud.id_utente = :uid
                    AND (:id_struttura1 = 0 OR d.id_struttura = :id_struttura2)
                  ORDER BY d.label"
            );
            $stmt->execute([
                ':uid'           => $id_utente,
                ':id_struttura1' => $id_struttura,
                ':id_struttura2' => $id_struttura,
            ]);
            $device = $stmt->fetchAll();
        }

        self::_json($device);
    }

    // ----------------------------------------------------------
    //  GET /api/contatori
    //  Restituisce i contatori per le dashboard
    // ----------------------------------------------------------
    public static function contatori(): void
    {
        self::_richiediLoginJson();

        $db           = Database::getInstance();
        $id_struttura = Auth::struttura_attiva();
        $ruolo        = Auth::ruolo();

        if ($ruolo === RUOLO_SUPERADMIN) {
            $data = [
                'strutture' => $db->query('SELECT COUNT(*) FROM gir_struttura WHERE attiva = 1')->fetchColumn(),
                'utenti'    => $db->query('SELECT COUNT(*) FROM gir_utenti WHERE attivo = 1')->fetchColumn(),
                'device'    => $db->query('SELECT COUNT(*) FROM gir_device WHERE attivo = 1')->fetchColumn(),
                'alert'     => $db->query('SELECT COUNT(*) FROM gir_alert WHERE chiuso_alle IS NULL')->fetchColumn(),
            ];
        } else {
            $strutture_ids = $id_struttura ? [$id_struttura] : Auth::strutture_accessibili();
            if (empty($strutture_ids)) {
                self::_json(['alert_rossi' => 0, 'alert_arancio' => 0]);
            }
            $ph   = implode(',', array_fill(0, count($strutture_ids), '?'));

            $rows = $db->prepare(
                "SELECT a.tipo, COUNT(*) AS n
                   FROM gir_alert a
                   JOIN gir_device d ON d.id = a.id_device
                  WHERE d.id_struttura IN ($ph) AND a.chiuso_alle IS NULL
                  GROUP BY a.tipo"
            );
            $rows->execute($strutture_ids);
            $per_tipo = array_column($rows->fetchAll(), 'n', 'tipo');

            $data = [
                'alert_rossi'   => (int)($per_tipo['ROSSO']    ?? 0),
                'alert_arancio' => (int)($per_tipo['ARANCIO']  ?? 0),
                'alert_batt'    => (int)($per_tipo['BATTERIA'] ?? 0),
                'alert_offline' => (int)($per_tipo['OFFLINE']  ?? 0),
                'alert_pulsante'=> (int)($per_tipo['PULSANTE'] ?? 0),
            ];
        }

        self::_json($data);
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    private static function _json(mixed $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function _richiediLoginJson(): void
    {
        if (!Auth::isLogged()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized']);
            exit;
        }
    }
}
