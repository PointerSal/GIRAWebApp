<?php
// ============================================================
//  GIRA · app/Controllers/DeviceController.php
//  Gestisce: lista device, crea, modifica, elimina,
//            assegnazione ubicazione
//  Accesso: superadmin + admin
// ============================================================

class DeviceController
{
    // ----------------------------------------------------------
    //  GET /device
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediAdmin();
        Middleware::verificaPasswordAggiornata();

        $db            = Database::getInstance();
        $strutture_ids = Auth::strutture_accessibili();

        if (empty($strutture_ids)) {
            $device = [];
        } else {
            $placeholders = implode(',', array_fill(0, count($strutture_ids), '?'));
            $stmt = $db->prepare(
                "SELECT d.*,
                        s.ragione_sociale AS struttura_nome,
                        u.area, u.subarea,
                        ds.posizione, ds.stato_batt, ds.stato_segnale,
                        ds.ultimo_contatto,
                        TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio
                   FROM gir_device d
                   JOIN gir_struttura s      ON s.id = d.id_struttura
              LEFT JOIN gir_ubicazione u     ON u.id = d.id_ubicazione
              LEFT JOIN gir_device_stato ds  ON ds.id_device = d.id
                  WHERE d.id_struttura IN ($placeholders)
                  ORDER BY s.ragione_sociale, d.label, d.mac"
            );
            $stmt->execute($strutture_ids);
            $device = $stmt->fetchAll();
        }

        $page_title   = 'Device — GIRA';
        $current_page = 'device';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'device/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /device/show/{id}
    // ----------------------------------------------------------
    public static function show(?int $id): void
    {
        Middleware::richiediAdmin();
        $device = self::_trova($id);
        Middleware::richiediAccessoStruttura((int)$device['id_struttura']);

        $db = Database::getInstance();

        // Stato attuale
        $stato = $db->prepare(
            'SELECT * FROM gir_device_stato WHERE id_device = :id LIMIT 1'
        );
        $stato->execute([':id' => $id]);
        $stato = $stato->fetch();

        // Storico posizioni (ultimi 7 giorni)
        $storico = $db->prepare(
            'SELECT * FROM gir_posizione_log
              WHERE id_device = :id
                AND iniziato_alle >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              ORDER BY iniziato_alle DESC
              LIMIT 50'
        );
        $storico->execute([':id' => $id]);
        $storico = $storico->fetchAll();

        // Alert aperti
        $alert = $db->prepare(
            'SELECT * FROM gir_alert
              WHERE id_device = :id AND chiuso_alle IS NULL
              ORDER BY aperto_alle DESC'
        );
        $alert->execute([':id' => $id]);
        $alert = $alert->fetchAll();

        // Ubicazione corrente
        $ubicazione = null;
        if ($device['id_ubicazione']) {
            $stmt = $db->prepare('SELECT * FROM gir_ubicazione WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $device['id_ubicazione']]);
            $ubicazione = $stmt->fetch();
        }

        $page_title   = htmlspecialchars($device['label'] ?? $device['mac']) . ' — GIRA';
        $current_page = 'device';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'device/show.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /device/crea?id_struttura=X
    // ----------------------------------------------------------
    public static function crea(): void
    {
        Middleware::richiediAdmin();

        $id_struttura = (int)($_GET['id_struttura'] ?? 0);
        $strutture    = self::_get_strutture();

        // Se viene da una struttura specifica, preselezionala
        $struttura_sel = $id_struttura ?: null;
        $ubicazioni    = $struttura_sel ? self::_get_ubicazioni($struttura_sel) : [];

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Nuovo device — GIRA';
        $current_page = 'device';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'device/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /device/crea-post
    // ----------------------------------------------------------
    public static function creaPost(): void
    {
        Middleware::richiediAdmin();

        $dati = self::_valida_form();
        if (!$dati) exit;

        Middleware::richiediAccessoStruttura($dati['id_struttura']);

        // Normalizza MAC
        $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $dati['mac']));
        if (strlen($mac) !== 12) {
            $_SESSION['errore']    = 'MAC address non valido (deve essere 12 caratteri hex).';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/device/crea');
            exit;
        }

        try {
            Database::getInstance()->prepare(
                'INSERT INTO gir_device (id_struttura, id_ubicazione, mac, label, attivo)
                 VALUES (:id_struttura, :id_ubicazione, :mac, :label, 1)'
            )->execute([
                ':id_struttura' => $dati['id_struttura'],
                ':id_ubicazione' => $dati['id_ubicazione'],
                ':mac'          => $mac,
                ':label'        => $dati['label'],
            ]);

            $id = (int)Database::getInstance()->lastInsertId();
            $_SESSION['successo'] = 'Device "' . ($dati['label'] ?? $mac) . '" registrato.';
            header('Location: ' . APP_URL . '/device/show/' . $id);
            exit;
        } catch (\Throwable $e) {
            $msg = str_contains($e->getMessage(), 'uq_mac')
                ? 'MAC address già registrato.'
                : 'Errore: ' . $e->getMessage();
            $_SESSION['errore']    = $msg;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/device/crea');
            exit;
        }
    }

    // ----------------------------------------------------------
    //  GET /device/modifica/{id}
    // ----------------------------------------------------------
    public static function modifica(?int $id): void
    {
        Middleware::richiediAdmin();
        $device = self::_trova($id);
        Middleware::richiediAccessoStruttura((int)$device['id_struttura']);

        $strutture  = self::_get_strutture();
        $ubicazioni = self::_get_ubicazioni((int)$device['id_struttura']);

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? (array)$device;
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Modifica device — GIRA';
        $current_page = 'device';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'device/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /device/modifica-post
    // ----------------------------------------------------------
    public static function modificaPost(): void
    {
        Middleware::richiediAdmin();

        $id     = (int)($_POST['id'] ?? 0);
        $device = self::_trova($id);
        Middleware::richiediAccessoStruttura((int)$device['id_struttura']);

        $dati = self::_valida_form();
        if (!$dati) exit;

        Database::getInstance()->prepare(
            'UPDATE gir_device
                SET id_ubicazione = :id_ubicazione,
                    label         = :label,
                    attivo        = :attivo
              WHERE id = :id'
        )->execute([
            ':id_ubicazione' => $dati['id_ubicazione'],
            ':label'         => $dati['label'],
            ':attivo'        => isset($_POST['attivo']) ? 1 : 0,
            ':id'            => $id,
        ]);

        $_SESSION['successo'] = 'Device aggiornato.';
        header('Location: ' . APP_URL . '/device/show/' . $id);
        exit;
    }

    // ----------------------------------------------------------
    //  GET /device/elimina/{id}
    // ----------------------------------------------------------
    public static function elimina(?int $id): void
    {
        Middleware::richiediAdmin();
        $device = self::_trova($id);
        Middleware::richiediAccessoStruttura((int)$device['id_struttura']);

        // Soft delete — disattiva invece di cancellare
        Database::getInstance()->prepare(
            'UPDATE gir_device SET attivo = 0 WHERE id = :id'
        )->execute([':id' => $id]);

        $_SESSION['successo'] = 'Device disattivato.';
        header('Location: ' . APP_URL . '/device');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /device/assegna/{id} — cambia ubicazione
    // ----------------------------------------------------------
    public static function assegna(?int $id): void
    {
        Middleware::richiediAdmin();
        $device = self::_trova($id);
        Middleware::richiediAccessoStruttura((int)$device['id_struttura']);

        $ubicazioni = self::_get_ubicazioni((int)$device['id_struttura']);
        $errore     = $_SESSION['errore'] ?? null;
        unset($_SESSION['errore']);

        $page_title   = 'Assegna ubicazione — GIRA';
        $current_page = 'device';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'device/assegna.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /device/assegna-post
    // ----------------------------------------------------------
    public static function assegnaPost(): void
    {
        Middleware::richiediAdmin();

        $id     = (int)($_POST['id'] ?? 0);
        $device = self::_trova($id);
        Middleware::richiediAccessoStruttura((int)$device['id_struttura']);

        $id_ubicazione = ($_POST['id_ubicazione'] ?? '') !== ''
            ? (int)$_POST['id_ubicazione']
            : null;

        Database::getInstance()->prepare(
            'UPDATE gir_device SET id_ubicazione = :id_ubicazione WHERE id = :id'
        )->execute([':id_ubicazione' => $id_ubicazione, ':id' => $id]);

        $_SESSION['successo'] = 'Ubicazione aggiornata.';
        header('Location: ' . APP_URL . '/device/show/' . $id);
        exit;
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    private static function _trova(?int $id): array
    {
        if (!$id) {
            $_SESSION['errore'] = 'ID non valido.';
            header('Location: ' . APP_URL . '/device');
            exit;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM gir_device WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $device = $stmt->fetch();

        if (!$device) {
            $_SESSION['errore'] = 'Device non trovato.';
            header('Location: ' . APP_URL . '/device');
            exit;
        }
        return $device;
    }

    private static function _valida_form(): ?array
    {
        $id_struttura  = (int)($_POST['id_struttura']  ?? 0);
        $id_ubicazione = ($_POST['id_ubicazione'] ?? '') !== '' ? (int)$_POST['id_ubicazione'] : null;
        $mac           = trim($_POST['mac'] ?? '');
        $label         = trim($_POST['label'] ?? '') ?: null;
        $id            = (int)($_POST['id'] ?? 0); // presente solo in modifica

        // In modifica il MAC non viene inviato (campo readonly)
        // quindi lo saltiamo dalla validazione
        if (!$id_struttura || (!$id && empty($mac))) {
            $_SESSION['errore']    = 'Struttura e MAC address sono obbligatori.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/device/' . ($id ? 'modifica/' . $id : 'crea'));
            exit;
        }

        return [
            'id_struttura'  => $id_struttura,
            'id_ubicazione' => $id_ubicazione,
            'mac'           => $mac,
            'label'         => $label,
        ];
    }

    public static function ubicazioniJson(): void
    {
        Middleware::richiediAdmin();
        $id_struttura = (int)($_GET['id_struttura'] ?? 0);
        Middleware::richiediAccessoStruttura($id_struttura);
        header('Content-Type: application/json');
        echo json_encode(self::_get_ubicazioni($id_struttura));
        exit;
    }

    public static function _get_strutture(): array
    {
        $ids  = Auth::strutture_accessibili();
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

    public static function _get_ubicazioni(int $id_struttura): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM gir_ubicazione
              WHERE id_struttura = :id
              ORDER BY area, subarea'
        );
        $stmt->execute([':id' => $id_struttura]);
        return $stmt->fetchAll();
    }
}
