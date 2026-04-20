<?php
// ============================================================
//  GIRA · app/Controllers/ReportController.php
//  Report device: alert ROSSO e OFFLINE per periodo
//  Accesso: admin + medico
// ============================================================

class ReportController
{
    // ----------------------------------------------------------
    //  GET /report
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediLogin();
        Middleware::verificaPasswordAggiornata();

        $db   = Database::getInstance();
        $user = Auth::utente();

        // Strutture accessibili (admin: struttura attiva o tutte, medico: tutte le sue)
        $strutture_ids = self::_strutture_accessibili();
        $strutture     = self::_get_strutture($strutture_ids);

        // ── Filtri ────────────────────────────────────────────
        $filtro_struttura = (int)($_GET['id_struttura'] ?? 0);
        $filtro_device    = isset($_GET['id_device']) ? array_map('intval', (array)$_GET['id_device']) : [];
        $filtro_tipi      = isset($_GET['tipi']) ? array_intersect((array)$_GET['tipi'], ['ROSSO', 'OFFLINE']) : ['ROSSO', 'OFFLINE'];

        // Periodo default: ultimi 7 giorni
        $data_da = $_GET['da'] ?? date('Y-m-d', strtotime('-7 days'));
        $data_a  = $_GET['a']  ?? date('Y-m-d');

        // Normalizza date
        $data_da = self::_valida_data($data_da, date('Y-m-d', strtotime('-7 days')));
        $data_a  = self::_valida_data($data_a,  date('Y-m-d'));

        // Se data_a < data_da, inverti
        if ($data_a < $data_da) [$data_da, $data_a] = [$data_a, $data_da];

        // ── Strutture filtrate ────────────────────────────────
        $ids_per_query = $filtro_struttura && in_array($filtro_struttura, $strutture_ids)
            ? [$filtro_struttura]
            : $strutture_ids;

        if (empty($ids_per_query)) {
            $righe   = [];
            $device  = [];
            $totali  = ['n_rossi' => 0, 'min_rossi' => 0, 'n_offline' => 0, 'min_offline' => 0];
            $page_title   = 'Report — GIRA';
            $current_page = 'report';
            include VIEW_PATH . 'layout/header.php';
            include VIEW_PATH . 'report/index.php';
            include VIEW_PATH . 'layout/footer.php';
            return;
        }

        // ── Device disponibili per il filtro dropdown ─────────
        $ph_s    = implode(',', array_fill(0, count($ids_per_query), '?'));
        $stmt    = $db->prepare(
            "SELECT d.id, d.label, d.mac, s.ragione_sociale AS struttura_nome,
                    u.area, u.subarea
               FROM gir_device d
               JOIN gir_struttura s      ON s.id = d.id_struttura
               LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
              WHERE d.id_struttura IN ($ph_s) AND d.attivo = 1
              ORDER BY u.area, u.subarea, d.label, d.mac"
        );
        $stmt->execute($ids_per_query);
        $device = $stmt->fetchAll();

        // Raggruppa device per area (per optgroup nella view)
        $device_per_area = [];
        foreach ($device as $dev) {
            $gruppo = $dev['area'] ?? '__nessuna__';
            $device_per_area[$gruppo][] = $dev;
        }

        // ── Query principale ──────────────────────────────────
        // Costruisce WHERE dinamico
        $where   = ["d.id_struttura IN ($ph_s)", 'd.attivo = 1'];
        $params  = $ids_per_query;

        if (!empty($filtro_device)) {
            $ph_d    = implode(',', array_fill(0, count($filtro_device), '?'));
            $where[] = "d.id IN ($ph_d)";
            $params  = array_merge($params, $filtro_device);
        }

        if (!empty($filtro_tipi)) {
            $ph_t    = implode(',', array_fill(0, count($filtro_tipi), '?'));
            $where[] = "(a.tipo IN ($ph_t) OR a.tipo IS NULL)";
            $params  = array_merge($params, $filtro_tipi);
        }

        $where_sql = implode(' AND ', $where);

        // Per ogni device: contatori e minuti aggregati per tipo
        $sql = "
            SELECT
                d.id,
                d.mac,
                COALESCE(d.label, d.mac)        AS label,
                s.ragione_sociale                AS struttura_nome,
                u.area,
                u.subarea,
                ds.ultimo_contatto,
                TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio,

                -- Alert ROSSO
                SUM(CASE WHEN a.tipo = 'ROSSO' THEN 1 ELSE 0 END)                          AS n_rossi,
                COALESCE(SUM(CASE WHEN a.tipo = 'ROSSO' THEN a.durata_minuti END), 0)       AS min_rossi,

                -- Posizione prevalente durante alert ROSSO
                -- (recuperata separatamente per semplicità — vedi subquery)
                NULL                                                                         AS pos_rosso,

                -- Alert OFFLINE
                SUM(CASE WHEN a.tipo = 'OFFLINE' THEN 1 ELSE 0 END)                        AS n_offline,
                COALESCE(SUM(CASE WHEN a.tipo = 'OFFLINE' THEN a.durata_minuti END), 0)     AS min_offline

            FROM gir_device d
            JOIN gir_struttura s         ON s.id = d.id_struttura
            LEFT JOIN gir_ubicazione u   ON u.id = d.id_ubicazione
            LEFT JOIN gir_device_stato ds ON ds.id_device = d.id
            LEFT JOIN gir_alert a        ON a.id_device = d.id
                                        AND a.tipo IN ('ROSSO','OFFLINE')
                                        AND a.aperto_alle >= :da
                                        AND a.aperto_alle <  DATE_ADD(:a, INTERVAL 1 DAY)
            WHERE $where_sql
            GROUP BY d.id, d.mac, d.label, s.ragione_sociale, u.area, u.subarea,
                     ds.ultimo_contatto
            ORDER BY s.ragione_sociale, d.label, d.mac
        ";

        $params_query = array_merge([':da' => $data_da, ':a' => $data_a], $params);
        // PDO non supporta mix named+positional — usiamo solo positional
        // Ricostruiamo con solo positional
        $sql_pos = str_replace([':da', ':a'], ['?', '?'], $sql);
        $params_pos = array_merge([$data_da, $data_a], $params);

        $stmt = $db->prepare($sql_pos);
        $stmt->execute($params_pos);
        $righe = $stmt->fetchAll();

        // ── Posizione prevalente per alert ROSSO ──────────────
        // Recupera per ogni device la posizione più frequente
        // negli alert ROSSO del periodo
        if (!empty($righe)) {
            $ids_device = array_column($righe, 'id');
            $ph_dev     = implode(',', array_fill(0, count($ids_device), '?'));
            $stmt2      = $db->prepare(
                "SELECT a.id_device,
                        pl.posizione,
                        COUNT(*) AS cnt
                   FROM gir_alert a
                   JOIN gir_posizione_log pl ON pl.id_device = a.id_device
                                            AND pl.iniziato_alle <= a.aperto_alle
                                            AND (pl.terminato_alle IS NULL OR pl.terminato_alle >= a.aperto_alle)
                  WHERE a.tipo = 'ROSSO'
                    AND a.aperto_alle >= ?
                    AND a.aperto_alle <  DATE_ADD(?, INTERVAL 1 DAY)
                    AND a.id_device IN ($ph_dev)
                  GROUP BY a.id_device, pl.posizione
                  ORDER BY a.id_device, cnt DESC"
            );
            $stmt2->execute(array_merge([$data_da, $data_a], $ids_device));
            $pos_rows = $stmt2->fetchAll();

            // Prendi la posizione con cnt più alto per device
            $pos_map = [];
            foreach ($pos_rows as $pr) {
                if (!isset($pos_map[$pr['id_device']])) {
                    $pos_map[$pr['id_device']] = $pr['posizione'];
                }
            }

            // Inietta nel risultato
            foreach ($righe as &$r) {
                $r['pos_rosso'] = $pos_map[$r['id']] ?? null;
            }
            unset($r);
        }

        // ── Totali ────────────────────────────────────────────
        $totali = [
            'n_rossi'    => array_sum(array_column($righe, 'n_rossi')),
            'min_rossi'  => array_sum(array_column($righe, 'min_rossi')),
            'n_offline'  => array_sum(array_column($righe, 'n_offline')),
            'min_offline' => array_sum(array_column($righe, 'min_offline')),
        ];

        $page_title   = 'Report — GIRA';
        $current_page = 'report';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'report/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    private static function _strutture_accessibili(): array
    {
        // Admin: usa struttura attiva o tutte le sue
        if (Auth::isAdmin()) {
            $id = Auth::struttura_attiva();
            return $id ? [$id] : Auth::strutture_accessibili();
        }
        // Medico: usa struttura attiva se impostata, altrimenti tutte le sue
        if (Auth::isMedico()) {
            $id = Auth::struttura_attiva();
            return $id ? [$id] : Auth::strutture_accessibili();
        }
        // Superadmin: tutte
        if (Auth::isSuperadmin()) {
            $stmt = Database::getInstance()->prepare(
                'SELECT id FROM gir_struttura WHERE attiva = 1 ORDER BY ragione_sociale'
            );
            $stmt->execute();
            return array_column($stmt->fetchAll(), 'id');
        }
        return [];
    }

    private static function _get_strutture(array $ids): array
    {
        if (empty($ids)) return [];
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::getInstance()->prepare(
            "SELECT id, ragione_sociale FROM gir_struttura
              WHERE id IN ($ph) AND attiva = 1
              ORDER BY ragione_sociale"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    private static function _valida_data(string $input, string $fallback): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $input);
        return ($d && $d->format('Y-m-d') === $input) ? $input : $fallback;
    }
}
