<?php
// ============================================================
//  GIRA · app/Controllers/StrutturaController.php
//  Gestisce: lista strutture RSA, CRUD, attiva/sospendi
//  Solo superadmin
// ============================================================

class StrutturaController
{
    // ----------------------------------------------------------
    //  GET /strutture
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediSuperadmin();

        $db = Database::getInstance();

        // Filtro opzionale ?stato=attiva|sospesa|tutte
        $filtri_validi = ['tutte', 'attiva', 'sospesa'];
        $filtro = in_array($_GET['stato'] ?? '', $filtri_validi) ? $_GET['stato'] : 'tutte';
        $where  = $filtro === 'attiva'  ? 'WHERE s.attiva = 1' :
                 ($filtro === 'sospesa' ? 'WHERE s.attiva = 0' : '');

        $strutture = $db->query(
            "SELECT s.*,
                    COUNT(DISTINCT d.id)  AS tot_device,
                    COUNT(DISTINCT us.id_utente) AS tot_utenti,
                    COUNT(DISTINCT a.id)  AS alert_aperti,
                    sub.piano             AS piano,
                    sub.stato             AS sub_stato,
                    sub.fine_il           AS sub_fine
               FROM gir_struttura s
          LEFT JOIN gir_device d           ON d.id_struttura = s.id AND d.attivo = 1
          LEFT JOIN gir_utente_struttura us ON us.id_struttura = s.id
          LEFT JOIN gir_alert a            ON a.id_device IN (
                                               SELECT id FROM gir_device WHERE id_struttura = s.id
                                             ) AND a.chiuso_alle IS NULL
          LEFT JOIN gir_subscription sub   ON sub.id_struttura = s.id
              $where
              GROUP BY s.id
              ORDER BY s.attiva DESC, s.ragione_sociale ASC"
        )->fetchAll();

        // Contatori per tab filtro
        $rows = $db->query(
            'SELECT attiva, COUNT(*) AS tot FROM gir_struttura GROUP BY attiva'
        )->fetchAll();
        $contatori = ['attiva' => 0, 'sospesa' => 0];
        foreach ($rows as $r) {
            $contatori[$r['attiva'] ? 'attiva' : 'sospesa'] = $r['tot'];
        }
        $contatori['tutte'] = array_sum($contatori);

        $page_title   = 'Strutture RSA — GIRA';
        $current_page = 'strutture';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'strutture/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /strutture/show/{id}
    // ----------------------------------------------------------
    public static function show(?int $id): void
    {
        Middleware::richiediSuperadmin();
        $struttura = self::_trova($id);

        $db = Database::getInstance();

        // Ubicazioni
        $ubicazioni = $db->prepare(
            'SELECT u.*, COUNT(d.id) AS tot_device
               FROM gir_ubicazione u
          LEFT JOIN gir_device d ON d.id_ubicazione = u.id AND d.attivo = 1
              WHERE u.id_struttura = :id
              GROUP BY u.id
              ORDER BY u.area, u.subarea'
        );
        $ubicazioni->execute([':id' => $id]);
        $ubicazioni = $ubicazioni->fetchAll();

        // Device della struttura
        $device = $db->prepare(
            'SELECT d.*, u.area, u.subarea,
                    ds.posizione, ds.stato_batt, ds.ultimo_contatto
               FROM gir_device d
          LEFT JOIN gir_ubicazione u      ON u.id = d.id_ubicazione
          LEFT JOIN gir_device_stato ds   ON ds.id_device = d.id
              WHERE d.id_struttura = :id
              ORDER BY d.label'
        );
        $device->execute([':id' => $id]);
        $device = $device->fetchAll();

        // Utenti della struttura
        $utenti = $db->prepare(
            'SELECT u.*, r.nome AS ruolo_nome
               FROM gir_utenti u
               JOIN gir_utente_struttura us ON us.id_utente = u.id
               JOIN gir_ruolo r             ON r.id = u.id_ruolo
              WHERE us.id_struttura = :id AND u.attivo = 1
              ORDER BY u.cognome, u.nome'
        );
        $utenti->execute([':id' => $id]);
        $utenti = $utenti->fetchAll();

        // Subscription
        $sub = $db->prepare(
            'SELECT * FROM gir_subscription WHERE id_struttura = :id LIMIT 1'
        );
        $sub->execute([':id' => $id]);
        $sub = $sub->fetch();

        $page_title   = htmlspecialchars($struttura['ragione_sociale']) . ' — GIRA';
        $current_page = 'strutture';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'strutture/show.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /strutture/crea
    // ----------------------------------------------------------
    public static function crea(): void
    {
        Middleware::richiediSuperadmin();

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Nuova struttura — GIRA';
        $current_page = 'strutture';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'strutture/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /strutture/crea-post
    // ----------------------------------------------------------
    public static function creaPost(): void
    {
        Middleware::richiediSuperadmin();

        $dati = self::_valida_form();
        if (!$dati) exit;

        $db = Database::getInstance();

        try {
            $db->prepare(
                'INSERT INTO gir_struttura
                    (ragione_sociale, partita_iva, indirizzo, telefono, mail, attiva)
                 VALUES
                    (:rs, :piva, :ind, :tel, :mail, 1)'
            )->execute([
                ':rs'   => $dati['ragione_sociale'],
                ':piva' => $dati['partita_iva'],
                ':ind'  => $dati['indirizzo'],
                ':tel'  => $dati['telefono'],
                ':mail' => $dati['mail'],
            ]);

            $id = (int)$db->lastInsertId();

            // Crea subscription FREE di default
            $db->prepare(
                'INSERT INTO gir_subscription (id_struttura, piano, stato, inizio_il)
                 VALUES (:id, "FREE", "ATTIVA", CURDATE())'
            )->execute([':id' => $id]);

            // Crea soglie default per la struttura
            $db->prepare(
                'INSERT INTO gir_soglie (id_struttura) VALUES (:id)'
            )->execute([':id' => $id]);

            $_SESSION['successo'] = 'Struttura "' . $dati['ragione_sociale'] . '" creata con successo.';
            header('Location: ' . APP_URL . '/strutture/show/' . $id);
            exit;

        } catch (\Throwable $e) {
            $msg = str_contains($e->getMessage(), 'partita_iva')
                ? 'Partita IVA già registrata.'
                : 'Errore durante la creazione: ' . $e->getMessage();
            $_SESSION['errore']    = $msg;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/strutture/crea');
            exit;
        }
    }

    // ----------------------------------------------------------
    //  GET /strutture/modifica/{id}
    // ----------------------------------------------------------
    public static function modifica(?int $id): void
    {
        Middleware::richiediSuperadmin();
        $struttura = self::_trova($id);

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? (array)$struttura;
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Modifica struttura — GIRA';
        $current_page = 'strutture';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'strutture/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /strutture/modifica-post
    // ----------------------------------------------------------
    public static function modificaPost(): void
    {
        Middleware::richiediSuperadmin();

        $id   = (int)($_POST['id'] ?? 0);
        $dati = self::_valida_form();
        if (!$dati) exit;

        self::_trova($id);

        try {
            Database::getInstance()->prepare(
                'UPDATE gir_struttura
                    SET ragione_sociale = :rs,
                        partita_iva     = :piva,
                        indirizzo       = :ind,
                        telefono        = :tel,
                        mail            = :mail
                  WHERE id = :id'
            )->execute([
                ':rs'   => $dati['ragione_sociale'],
                ':piva' => $dati['partita_iva'],
                ':ind'  => $dati['indirizzo'],
                ':tel'  => $dati['telefono'],
                ':mail' => $dati['mail'],
                ':id'   => $id,
            ]);

            $_SESSION['successo'] = 'Struttura aggiornata.';
            header('Location: ' . APP_URL . '/strutture/show/' . $id);
            exit;

        } catch (\Throwable $e) {
            $msg = str_contains($e->getMessage(), 'partita_iva')
                ? 'Partita IVA già registrata.'
                : 'Errore: ' . $e->getMessage();
            $_SESSION['errore']    = $msg;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/strutture/modifica/' . $id);
            exit;
        }
    }

    // ----------------------------------------------------------
    //  GET /strutture/attiva/{id}
    // ----------------------------------------------------------
    public static function attiva(?int $id): void
    {
        Middleware::richiediSuperadmin();
        self::_trova($id);

        Database::getInstance()
            ->prepare('UPDATE gir_struttura SET attiva = 1 WHERE id = :id')
            ->execute([':id' => $id]);

        $_SESSION['successo'] = 'Struttura riattivata.';
        header('Location: ' . APP_URL . '/strutture');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /strutture/sospendi/{id}
    // ----------------------------------------------------------
    public static function sospendi(?int $id): void
    {
        Middleware::richiediSuperadmin();
        self::_trova($id);

        Database::getInstance()
            ->prepare('UPDATE gir_struttura SET attiva = 0 WHERE id = :id')
            ->execute([':id' => $id]);

        $_SESSION['successo'] = 'Struttura sospesa.';
        header('Location: ' . APP_URL . '/strutture');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /strutture/elimina/{id}
    //  Soft delete — disattiva invece di cancellare
    // ----------------------------------------------------------
    public static function elimina(?int $id): void
    {
        Middleware::richiediSuperadmin();
        self::_trova($id);

        // Verifica che non ci siano device attivi
        $tot = Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM gir_device WHERE id_struttura = :id AND attivo = 1')
            ->execute([':id' => $id]);

        // Disattiva struttura e device collegati
        $db = Database::getInstance();
        $db->prepare('UPDATE gir_struttura SET attiva = 0 WHERE id = :id')->execute([':id' => $id]);
        $db->prepare('UPDATE gir_device SET attivo = 0 WHERE id_struttura = :id')->execute([':id' => $id]);

        $_SESSION['successo'] = 'Struttura disattivata.';
        header('Location: ' . APP_URL . '/strutture');
        exit;
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    /**
     * Trova una struttura per ID o reindirizza con errore.
     */
    private static function _trova(?int $id): array
    {
        if (!$id) {
            $_SESSION['errore'] = 'ID non valido.';
            header('Location: ' . APP_URL . '/strutture');
            exit;
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM gir_struttura WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $struttura = $stmt->fetch();

        if (!$struttura) {
            $_SESSION['errore'] = 'Struttura non trovata.';
            header('Location: ' . APP_URL . '/strutture');
            exit;
        }

        return $struttura;
    }

    /**
     * Valida i campi del form crea/modifica.
     * Ritorna array dati puliti o reindirizza con errore.
     */
    private static function _valida_form(): ?array
    {
        $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
        $partita_iva     = trim($_POST['partita_iva']     ?? '');
        $indirizzo       = trim($_POST['indirizzo']       ?? '');
        $telefono        = trim($_POST['telefono']        ?? '');
        $mail            = trim($_POST['mail']            ?? '');

        if (empty($ragione_sociale) || empty($partita_iva)) {
            $_SESSION['errore']    = 'Ragione sociale e Partita IVA sono obbligatorie.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/strutture/' .
                (!empty($_POST['id']) ? 'modifica/' . (int)$_POST['id'] : 'crea'));
            exit;
        }

        if (!preg_match('/^\d{11}$/', $partita_iva)) {
            $_SESSION['errore']    = 'La Partita IVA deve essere di 11 cifre.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/strutture/' .
                (!empty($_POST['id']) ? 'modifica/' . (int)$_POST['id'] : 'crea'));
            exit;
        }

        return [
            'ragione_sociale' => $ragione_sociale,
            'partita_iva'     => $partita_iva,
            'indirizzo'       => $indirizzo ?: null,
            'telefono'        => $telefono  ?: null,
            'mail'            => $mail      ?: null,
        ];
    }
}
