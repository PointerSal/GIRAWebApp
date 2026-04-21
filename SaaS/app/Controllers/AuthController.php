<?php
// ============================================================
//  GIRA · app/Controllers/AuthController.php
//  Gestisce: login, logout, cambio password
//
//  Nota: in GIRA non esiste registrazione pubblica.
//  Gli utenti vengono creati dal superadmin o dall'admin
//  tramite UtenteController.
// ============================================================

class AuthController
{
    // ----------------------------------------------------------
    //  GET /auth/login
    // ----------------------------------------------------------
    public static function login(): void
    {
        if (Auth::isLogged()) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        $errore = $_SESSION['errore'] ?? null;
        unset($_SESSION['errore']);
        include VIEW_PATH . 'auth/login.php';
    }

    // ----------------------------------------------------------
    //  POST /auth/login-post
    // ----------------------------------------------------------
    public static function loginPost(): void
    {
        $mail     = trim($_POST['mail']     ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($mail) || empty($password)) {
            $_SESSION['errore'] = 'Inserisci email e password.';
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        $risultato = Auth::login($mail, $password);

        if (!$risultato['ok']) {
            $_SESSION['errore'] = $risultato['errore'];
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        // Forza cambio password se necessario
        if (!empty(Auth::utente()['deve_cambiare_pwd'])) {
            header('Location: ' . APP_URL . '/auth/cambia-password');
            exit;
        }

        // Login OK — remember token + dashboard
        $_SESSION['forza_push_sync'] = true;
        Auth::crea_remember_token((int)Auth::utente()['id']);
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    }

    // ----------------------------------------------------------
    //  GET /auth/logout
    // ----------------------------------------------------------
    public static function logout(): void
    {
        Auth::logout();
        header('Location: ' . APP_URL);
        exit;
    }

    // ----------------------------------------------------------
    //  GET+POST /auth/cambia-password
    //  Forzato al primo accesso se deve_cambiare_pwd = 1
    //  Disponibile anche volontariamente dal profilo
    // ----------------------------------------------------------
    public static function cambiaPassword(): void
    {
        Middleware::richiediLogin();
        Middleware::verificaPasswordAggiornata();

        $errore   = $_SESSION['errore']   ?? null;
        $successo = $_SESSION['successo'] ?? null;
        unset($_SESSION['errore'], $_SESSION['successo']);

        include VIEW_PATH . 'auth/cambia_password.php';
    }

    public static function cambiaPasswordPost(): void
    {
        Middleware::richiediLogin();

        $vecchia = $_POST['vecchia'] ?? '';
        $nuova   = $_POST['nuova']   ?? '';
        $nuova2  = $_POST['nuova2']  ?? '';

        if (empty($vecchia) || empty($nuova) || empty($nuova2)) {
            $_SESSION['errore'] = 'Tutti i campi sono obbligatori.';
            header('Location: ' . APP_URL . '/auth/cambia-password');
            exit;
        }
        if ($nuova !== $nuova2) {
            $_SESSION['errore'] = 'Le due password non coincidono.';
            header('Location: ' . APP_URL . '/auth/cambia-password');
            exit;
        }
        if (strlen($nuova) < 8) {
            $_SESSION['errore'] = 'La password deve essere di almeno 8 caratteri.';
            header('Location: ' . APP_URL . '/auth/cambia-password');
            exit;
        }

        try {
            Auth::cambia_password((int)Auth::utente()['id'], $vecchia, $nuova);
            $_SESSION['successo'] = 'Password aggiornata con successo.';
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['errore'] = $e->getMessage();
            header('Location: ' . APP_URL . '/auth/cambia-password');
            exit;
        }
    }

    // ----------------------------------------------------------
    //  GET /auth/profilo
    //  Visualizza il profilo dell'utente loggato
    // ----------------------------------------------------------
    public static function profilo(): void
    {
        Middleware::richiediLogin();
        $utente = Auth::utente();

        // Carica nome ruolo
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT nome FROM gir_ruolo WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $utente['id_ruolo']]);
        $utente['ruolo_nome'] = $stmt->fetchColumn() ?: '';

        // Carica preferenze notifiche
        $stmt = $db->prepare('SELECT * FROM gir_notifica_preferenze WHERE id_utente = :id LIMIT 1');
        $stmt->execute([':id' => $utente['id']]);
        $pref = $stmt->fetch() ?: [];

        $errore   = $_SESSION['errore']   ?? null;
        $successo = $_SESSION['successo'] ?? null;
        unset($_SESSION['errore'], $_SESSION['successo']);
        include VIEW_PATH . 'auth/profilo.php';
    }

    // ----------------------------------------------------------
    //  POST /auth/profilo-post
    //  Aggiorna nome, cognome, telefono dell'utente loggato
    // ----------------------------------------------------------
    public static function profiloPost(): void
    {
        Middleware::richiediLogin();

        $nome     = trim($_POST['nome']     ?? '');
        $cognome  = trim($_POST['cognome']  ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (empty($nome) || empty($cognome)) {
            $_SESSION['errore'] = 'Nome e cognome sono obbligatori.';
            header('Location: ' . APP_URL . '/auth/profilo');
            exit;
        }

        $db = Database::getInstance();
        $db->prepare(
            'UPDATE gir_utenti
                SET nome = :nome, cognome = :cognome, telefono = :tel
              WHERE id = :id'
        )->execute([
            ':nome'    => $nome,
            ':cognome' => $cognome,
            ':tel'     => $telefono ?: null,
            ':id'      => Auth::id(),
        ]);

        // Aggiorna sessione
        $_SESSION['utente']['nome']     = $nome;
        $_SESSION['utente']['cognome']  = $cognome;
        $_SESSION['utente']['telefono'] = $telefono;

        $_SESSION['successo'] = 'Profilo aggiornato.';
        header('Location: ' . APP_URL . '/auth/profilo');
        exit;
    }

    // ----------------------------------------------------------
    //  POST /auth/preferenze-post
    // ----------------------------------------------------------
    public static function preferenzePost(): void
    {
        Middleware::richiediLogin();

        $id = Auth::id();
        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO gir_notifica_preferenze
                (id_utente, push_attiva, alert_rosso, alert_arancio, alert_batteria, alert_offline)
             VALUES
                (:id, :push, :rosso, :arancio, :batt, :offline)
             ON DUPLICATE KEY UPDATE
                push_attiva    = VALUES(push_attiva),
                alert_rosso    = VALUES(alert_rosso),
                alert_arancio  = VALUES(alert_arancio),
                alert_batteria = VALUES(alert_batteria),
                alert_offline  = VALUES(alert_offline)'
        )->execute([
            ':id'      => $id,
            ':push'    => isset($_POST['push_attiva'])    ? 1 : 0,
            ':rosso'   => isset($_POST['alert_rosso'])    ? 1 : 0,
            ':arancio' => isset($_POST['alert_arancio'])  ? 1 : 0,
            ':batt'    => isset($_POST['alert_batteria']) ? 1 : 0,
            ':offline' => isset($_POST['alert_offline'])  ? 1 : 0,
        ]);

        $_SESSION['successo'] = 'Preferenze notifiche salvate.';
        header('Location: ' . APP_URL . '/auth/profilo');
        exit;
    }
}
