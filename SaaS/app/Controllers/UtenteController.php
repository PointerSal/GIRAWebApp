<?php
// ============================================================
//  GIRA · app/Controllers/UtenteController.php
//  Gestisce: lista utenti, crea, modifica, elimina,
//            reset password, preferenze notifiche,
//            device assegnati (per operatori)
// ============================================================

class UtenteController
{
    // ----------------------------------------------------------
    //  GET /utenti
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediAdmin();
        Middleware::verificaPasswordAggiornata();

        $db            = Database::getInstance();
        //$strutture_ids = Auth::strutture_accessibili();
        $id_struttura  = Auth::struttura_attiva();
        $strutture_ids = $id_struttura ? [$id_struttura] : Auth::strutture_accessibili();

        // Filtro struttura opzionale
        $filtro_struttura = (int)($_GET['id_struttura'] ?? 0);

        if (Auth::isSuperadmin()) {
            // Superadmin vede tutti
            $where  = $filtro_struttura ? 'WHERE us.id_struttura = :fid' : '';
            $params = $filtro_struttura ? [':fid' => $filtro_struttura] : [];
            $utenti = $db->prepare(
                "SELECT u.*, r.nome AS ruolo_nome,
                        GROUP_CONCAT(s.ragione_sociale ORDER BY s.ragione_sociale SEPARATOR ', ') AS strutture
                   FROM gir_utenti u
                   JOIN gir_ruolo r ON r.id = u.id_ruolo
              LEFT JOIN gir_utente_struttura us ON us.id_utente = u.id
              LEFT JOIN gir_struttura s          ON s.id = us.id_struttura
                  $where
                  GROUP BY u.id
                  ORDER BY u.cognome, u.nome"
            );
            $utenti->execute($params);
        } else {
            // Admin vede solo utenti delle sue strutture
            $ph     = implode(',', array_fill(0, count($strutture_ids), '?'));
            $utenti = $db->prepare(
                "SELECT u.*, r.nome AS ruolo_nome,
                        GROUP_CONCAT(s.ragione_sociale ORDER BY s.ragione_sociale SEPARATOR ', ') AS strutture
                   FROM gir_utenti u
                   JOIN gir_ruolo r              ON r.id = u.id_ruolo
                   JOIN gir_utente_struttura us  ON us.id_utente = u.id
                   JOIN gir_struttura s          ON s.id = us.id_struttura
                  WHERE us.id_struttura IN ($ph)
                  GROUP BY u.id
                  ORDER BY u.cognome, u.nome"
            );
            $utenti->execute($strutture_ids);
        }
        $utenti = $utenti->fetchAll();

        // Strutture per filtro dropdown (solo superadmin)
        $strutture_map = [];
        if (Auth::isSuperadmin()) {
            $rows = $db->query('SELECT id, ragione_sociale FROM gir_struttura WHERE attiva = 1 ORDER BY ragione_sociale')->fetchAll();
            foreach ($rows as $r) $strutture_map[$r['id']] = $r['ragione_sociale'];
        }

        $page_title   = 'Utenti — GIRA';
        $current_page = 'utenti';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'utenti/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  GET /utenti/crea
    // ----------------------------------------------------------
    public static function crea(): void
    {
        Middleware::richiediAdmin();
        Middleware::verificaPasswordAggiornata();

        $db         = Database::getInstance();
        $strutture  = self::_get_strutture();
        $ruoli      = self::_get_ruoli_disponibili();

        // Preseleziona struttura se passata via GET
        $id_struttura_sel = (int)($_GET['id_struttura'] ?? 0);

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Nuovo utente — GIRA';
        $current_page = 'utenti';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'utenti/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /utenti/crea-post
    // ----------------------------------------------------------
    public static function creaPost(): void
    {
        Middleware::richiediAdmin();

        $dati = self::_valida_form();
        if (!$dati) exit;

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // Crea utente
            $db->prepare(
                'INSERT INTO gir_utenti
                    (id_ruolo, nome, cognome, telefono, mail, password_hash,
                     deve_cambiare_pwd, attivo)
                 VALUES
                    (:ruolo, :nome, :cognome, :tel, :mail, :hash, 1, 1)'
            )->execute([
                ':ruolo'   => $dati['id_ruolo'],
                ':nome'    => $dati['nome'],
                ':cognome' => $dati['cognome'],
                ':tel'     => $dati['telefono'],
                ':mail'    => $dati['mail'],
                ':hash'    => password_hash($dati['password'], PASSWORD_BCRYPT),
            ]);
            $id_utente = (int)$db->lastInsertId();

            // Associa strutture
            foreach ($dati['strutture_ids'] as $sid) {
                $db->prepare(
                    'INSERT IGNORE INTO gir_utente_struttura (id_utente, id_struttura)
                     VALUES (:uid, :sid)'
                )->execute([':uid' => $id_utente, ':sid' => $sid]);
            }

            // Crea preferenze notifiche default
            $db->prepare(
                'INSERT INTO gir_notifica_preferenze (id_utente) VALUES (:id)'
            )->execute([':id' => $id_utente]);

            $db->commit();

            $_SESSION['successo'] = 'Utente creato. Al primo accesso dovrà cambiare la password.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        } catch (\Throwable $e) {
            $db->rollBack();
            $msg = str_contains($e->getMessage(), 'uq_mail')
                ? 'Email già registrata.'
                : 'Errore: ' . $e->getMessage();
            $_SESSION['errore']    = $msg;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/utenti/crea');
            exit;
        }
    }

    // ----------------------------------------------------------
    //  GET /utenti/modifica/{id}
    // ----------------------------------------------------------
    public static function modifica(?int $id): void
    {
        Middleware::richiediAdmin();
        $utente_target = self::_trova($id);
        self::_verifica_accesso($utente_target);

        $strutture         = self::_get_strutture();
        $ruoli             = self::_get_ruoli_disponibili();
        $strutture_utente  = self::_strutture_utente($id);

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? array_merge(
            (array)$utente_target,
            ['strutture_ids' => array_column($strutture_utente, 'id')]
        );
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Modifica utente — GIRA';
        $current_page = 'utenti';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'utenti/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /utenti/modifica-post
    // ----------------------------------------------------------
    public static function modificaPost(): void
    {
        Middleware::richiediAdmin();

        $id            = (int)($_POST['id'] ?? 0);
        $utente_target = self::_trova($id);
        self::_verifica_accesso($utente_target);

        $dati = self::_valida_form(modifica: true);
        if (!$dati) exit;

        // Non si può fare downgrade di se stesso
        if ($id === Auth::id()) {
            $dati['id_ruolo'] = Auth::ruolo();
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $db->prepare(
                'UPDATE gir_utenti
                    SET id_ruolo  = :ruolo,
                        nome      = :nome,
                        cognome   = :cognome,
                        telefono  = :tel,
                        mail      = :mail,
                        attivo    = :attivo
                  WHERE id = :id'
            )->execute([
                ':ruolo'   => $dati['id_ruolo'],
                ':nome'    => $dati['nome'],
                ':cognome' => $dati['cognome'],
                ':tel'     => $dati['telefono'],
                ':mail'    => $dati['mail'],
                ':attivo'  => $dati['attivo'],
                ':id'      => $id,
            ]);

            // Aggiorna strutture — prima rimuovi tutte, poi reinserisci
            // (solo se superadmin o admin può cambiare strutture)
            if (Auth::isAdmin()) {
                $db->prepare('DELETE FROM gir_utente_struttura WHERE id_utente = :uid')
                    ->execute([':uid' => $id]);
                foreach ($dati['strutture_ids'] as $sid) {
                    $db->prepare(
                        'INSERT IGNORE INTO gir_utente_struttura (id_utente, id_struttura)
                         VALUES (:uid, :sid)'
                    )->execute([':uid' => $id, ':sid' => $sid]);
                }
            }

            $db->commit();

            $_SESSION['successo'] = 'Utente aggiornato.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        } catch (\Throwable $e) {
            $db->rollBack();
            $msg = str_contains($e->getMessage(), 'uq_mail')
                ? 'Email già registrata.'
                : 'Errore: ' . $e->getMessage();
            $_SESSION['errore']    = $msg;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/utenti/modifica/' . $id);
            exit;
        }
    }

    // ----------------------------------------------------------
    //  GET /utenti/elimina/{id}
    // ----------------------------------------------------------
    public static function elimina(?int $id): void
    {
        Middleware::richiediAdmin();
        $target = self::_trova($id);
        self::_verifica_accesso($target);

        // Non può eliminare se stesso
        if ($id === Auth::id()) {
            $_SESSION['errore'] = 'Non puoi eliminare il tuo stesso account.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        }

        // Non può eliminare un superadmin
        if ((int)$target['id_ruolo'] === RUOLO_SUPERADMIN && !Auth::isSuperadmin()) {
            $_SESSION['errore'] = 'Non puoi eliminare un superadmin.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        }

        // Soft delete — disattiva
        Database::getInstance()
            ->prepare('UPDATE gir_utenti SET attivo = 0 WHERE id = :id')
            ->execute([':id' => $id]);

        $_SESSION['successo'] = 'Utente disattivato.';
        header('Location: ' . APP_URL . '/utenti');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /utenti/reset-pwd/{id}
    //  POST /utenti/reset-pwd-post
    // ----------------------------------------------------------
    public static function resetPassword(?int $id): void
    {
        Middleware::richiediAdmin();
        $target = self::_trova($id);
        self::_verifica_accesso($target);

        $errore   = $_SESSION['errore']   ?? null;
        $successo = $_SESSION['successo'] ?? null;
        unset($_SESSION['errore'], $_SESSION['successo']);

        $page_title   = 'Reset password — GIRA';
        $current_page = 'utenti';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'utenti/reset_pwd.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public static function resetPasswordPost(): void
    {
        Middleware::richiediAdmin();

        $id       = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if (!$id || strlen($password) < 8) {
            $_SESSION['errore'] = 'Password non valida (minimo 8 caratteri).';
            header('Location: ' . APP_URL . '/utenti/reset-pwd/' . $id);
            exit;
        }

        try {
            Auth::reset_password($id, $password);
            $_SESSION['successo'] = 'Password reimpostata. L\'utente dovrà cambiarla al prossimo accesso.';
        } catch (\Throwable $e) {
            $_SESSION['errore'] = $e->getMessage();
        }

        header('Location: ' . APP_URL . '/utenti');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /utenti/preferenze/{id}
    //  POST /utenti/preferenze-post
    // ----------------------------------------------------------
    public static function preferenze(?int $id): void
    {
        Middleware::richiediAdmin();
        $target = self::_trova($id);
        self::_verifica_accesso($target);

        $db   = Database::getInstance();
        $pref = $db->prepare(
            'SELECT * FROM gir_notifica_preferenze WHERE id_utente = :id LIMIT 1'
        );
        $pref->execute([':id' => $id]);
        $pref = $pref->fetch() ?: [];

        $errore   = $_SESSION['errore']   ?? null;
        $successo = $_SESSION['successo'] ?? null;
        unset($_SESSION['errore'], $_SESSION['successo']);

        $page_title   = 'Preferenze notifiche — GIRA';
        $current_page = 'utenti';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'utenti/preferenze.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public static function preferenzePost(): void
    {
        Middleware::richiediAdmin();

        $id = (int)($_POST['id'] ?? 0);
        self::_trova($id);

        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO gir_notifica_preferenze
                (id_utente, push_attiva, mail_attiva, mail_istantanea, mail_riepilogo,
                 alert_arancio, alert_rosso, alert_batteria, alert_offline, ora_riepilogo)
             VALUES
                (:id, :push, :mail, :mail_ist, :mail_rip,
                 :arancio, :rosso, :batt, :offline, :ora)
             ON DUPLICATE KEY UPDATE
                push_attiva     = VALUES(push_attiva),
                mail_attiva     = VALUES(mail_attiva),
                mail_istantanea = VALUES(mail_istantanea),
                mail_riepilogo  = VALUES(mail_riepilogo),
                alert_arancio   = VALUES(alert_arancio),
                alert_rosso     = VALUES(alert_rosso),
                alert_batteria  = VALUES(alert_batteria),
                alert_offline   = VALUES(alert_offline),
                ora_riepilogo   = VALUES(ora_riepilogo)'
        )->execute([
            ':id'       => $id,
            ':push'     => isset($_POST['push_attiva'])     ? 1 : 0,
            ':mail'     => isset($_POST['mail_attiva'])     ? 1 : 0,
            ':mail_ist' => isset($_POST['mail_istantanea']) ? 1 : 0,
            ':mail_rip' => isset($_POST['mail_riepilogo'])  ? 1 : 0,
            ':arancio'  => isset($_POST['alert_arancio'])   ? 1 : 0,
            ':rosso'    => isset($_POST['alert_rosso'])     ? 1 : 0,
            ':batt'     => isset($_POST['alert_batteria'])  ? 1 : 0,
            ':offline'  => isset($_POST['alert_offline'])   ? 1 : 0,
            ':ora'      => (int)($_POST['ora_riepilogo'] ?? 7),
        ]);

        $_SESSION['successo'] = 'Preferenze salvate.';
        header('Location: ' . APP_URL . '/utenti/preferenze/' . $id);
        exit;
    }

    // ----------------------------------------------------------
    //  GET /utenti/device-assegnati/{id}
    //  POST /utenti/device-assegnati-post
    //  Solo per operatori — quali device monitorano
    // ----------------------------------------------------------
    public static function deviceAssegnati(?int $id): void
    {
        Middleware::richiediAdmin();
        $target = self::_trova($id);
        self::_verifica_accesso($target);

        // Solo per operatori
        if ((int)$target['id_ruolo'] !== RUOLO_UTENTE) {
            $_SESSION['errore'] = 'L\'assegnazione device è disponibile solo per gli operatori.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        }

        $db = Database::getInstance();

        // Strutture dell'operatore
        $strutture_op = self::_strutture_utente($id);
        $ids_strutture = array_column($strutture_op, 'id');

        $device_disponibili = [];
        if (!empty($ids_strutture)) {
            $ph   = implode(',', array_fill(0, count($ids_strutture), '?'));
            $stmt = $db->prepare(
                "SELECT d.*, s.ragione_sociale AS struttura_nome,
                        u.area, u.subarea
                   FROM gir_device d
                   JOIN gir_struttura s     ON s.id = d.id_struttura
              LEFT JOIN gir_ubicazione u    ON u.id = d.id_ubicazione
                  WHERE d.id_struttura IN ($ph) AND d.attivo = 1
                  ORDER BY s.ragione_sociale, d.label"
            );
            $stmt->execute($ids_strutture);
            $device_disponibili = $stmt->fetchAll();
        }

        // Device già assegnati
        $assegnati = $db->prepare(
            'SELECT id_device FROM gir_utente_device WHERE id_utente = :uid'
        );
        $assegnati->execute([':uid' => $id]);
        $ids_assegnati = array_column($assegnati->fetchAll(), 'id_device');

        $errore   = $_SESSION['errore']   ?? null;
        $successo = $_SESSION['successo'] ?? null;
        unset($_SESSION['errore'], $_SESSION['successo']);

        $page_title   = 'Device assegnati — GIRA';
        $current_page = 'utenti';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'utenti/device_assegnati.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public static function deviceAssegnatiPost(): void
    {
        Middleware::richiediAdmin();

        $id = (int)($_POST['id'] ?? 0);
        self::_trova($id);

        $db          = Database::getInstance();
        $device_ids  = array_map('intval', $_POST['device_ids'] ?? []);

        // Sostituisci tutte le assegnazioni
        $db->prepare('DELETE FROM gir_utente_device WHERE id_utente = :uid')
            ->execute([':uid' => $id]);

        foreach ($device_ids as $did) {
            $db->prepare(
                'INSERT IGNORE INTO gir_utente_device (id_utente, id_device)
                 VALUES (:uid, :did)'
            )->execute([':uid' => $id, ':did' => $did]);
        }

        $_SESSION['successo'] = 'Device assegnati aggiornati.';
        header('Location: ' . APP_URL . '/utenti/device-assegnati/' . $id);
        exit;
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    private static function _trova(?int $id): array
    {
        if (!$id) {
            $_SESSION['errore'] = 'ID non valido.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM gir_utenti WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $utente = $stmt->fetch();
        if (!$utente) {
            $_SESSION['errore'] = 'Utente non trovato.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        }
        return $utente;
    }

    private static function _verifica_accesso(array $target): void
    {
        if (Auth::isSuperadmin()) return;

        // Verifica che il target appartenga a una struttura dell'admin
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT 1 FROM gir_utente_struttura us1
               JOIN gir_utente_struttura us2 ON us2.id_struttura = us1.id_struttura
              WHERE us1.id_utente = :admin AND us2.id_utente = :target
              LIMIT 1'
        );
        $stmt->execute([':admin' => Auth::id(), ':target' => $target['id']]);
        if (!$stmt->fetch()) {
            $_SESSION['errore'] = 'Non puoi gestire utenti di un\'altra struttura.';
            header('Location: ' . APP_URL . '/utenti');
            exit;
        }
    }

    private static function _valida_form(bool $modifica = false): ?array
    {
        $nome      = trim($_POST['nome']     ?? '');
        $cognome   = trim($_POST['cognome']  ?? '');
        $mail      = trim($_POST['mail']     ?? '');
        $telefono  = trim($_POST['telefono'] ?? '') ?: null;
        $id_ruolo  = (int)($_POST['id_ruolo'] ?? RUOLO_UTENTE);
        $password  = $_POST['password'] ?? '';
        $attivo    = isset($_POST['attivo']) ? 1 : 0;
        $strutture_ids = array_map('intval', $_POST['strutture_ids'] ?? []);

        $id = (int)($_POST['id'] ?? 0);

        if (empty($nome) || empty($cognome) || empty($mail)) {
            $_SESSION['errore']    = 'Nome, cognome e email sono obbligatori.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/utenti/' . ($modifica ? 'modifica/' . $id : 'crea'));
            exit;
        }

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errore']    = 'Indirizzo email non valido.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/utenti/' . ($modifica ? 'modifica/' . $id : 'crea'));
            exit;
        }

        if (!$modifica && strlen($password) < 8) {
            $_SESSION['errore']    = 'La password deve essere di almeno 8 caratteri.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . APP_URL . '/utenti/crea');
            exit;
        }

        // Un admin non può creare superadmin
        if (!Auth::isSuperadmin() && $id_ruolo === RUOLO_SUPERADMIN) {
            $id_ruolo = RUOLO_UTENTE;
        }

        return [
            'nome'         => $nome,
            'cognome'      => $cognome,
            'mail'         => $mail,
            'telefono'     => $telefono,
            'id_ruolo'     => $id_ruolo,
            'password'     => $password,
            'attivo'       => $attivo,
            'strutture_ids' => $strutture_ids,
        ];
    }

    private static function _get_strutture(): array
    {
        $ids = Auth::strutture_accessibili();
        if (empty($ids)) return [];
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::getInstance()->prepare(
            "SELECT id, ragione_sociale FROM gir_struttura
              WHERE id IN ($ph) AND attiva = 1
              ORDER BY ragione_sociale"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    private static function _get_ruoli_disponibili(): array
    {
        // Superadmin vede tutti i ruoli, admin non può creare superadmin
        $db   = Database::getInstance();
        $stmt = Auth::isSuperadmin()
            ? $db->query('SELECT * FROM gir_ruolo ORDER BY id')
            : $db->prepare('SELECT * FROM gir_ruolo WHERE id != :sid');

        if (!Auth::isSuperadmin()) {
            $stmt->execute([':sid' => RUOLO_SUPERADMIN]);
        }
        return $stmt->fetchAll();
    }

    private static function _strutture_utente(int $id): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT s.id, s.ragione_sociale
               FROM gir_struttura s
               JOIN gir_utente_struttura us ON us.id_struttura = s.id
              WHERE us.id_utente = :id
              ORDER BY s.ragione_sociale'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }
}
