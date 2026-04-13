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
        $utente   = Auth::utente();
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
}
