<?php
// ============================================================
//  GIRA · app/Core/Middleware.php
// ============================================================

class Middleware
{
    // ----------------------------------------------------------
    //  Richiede login — verifica anche stato struttura
    // ----------------------------------------------------------
    public static function richiediLogin(): void
    {
        if (!Auth::isLogged()) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        // Superadmin: nessun controllo struttura
        if (Auth::isSuperadmin()) return;

        // Verifica stato struttura direttamente dal DB (non dalla sessione)
        $utente = Auth::utente();
        $db     = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT s.attiva
               FROM gir_utente_struttura us
               JOIN gir_struttura s ON s.id = us.id_struttura
              WHERE us.id_utente = :uid
              LIMIT 1'
        );
        $stmt->execute([':uid' => (int)$utente['id']]);
        $struttura = $stmt->fetch();

        if ($struttura && !(int)$struttura['attiva']) {
            Auth::logout();
            $_SESSION['errore'] = 'La tua struttura è stata sospesa. Contatta il supporto.';
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
    }

    // ----------------------------------------------------------
    //  Richiede un ruolo specifico o superiore
    //  Uso: Middleware::richiediRuolo([RUOLO_ADMIN, RUOLO_MEDICO])
    // ----------------------------------------------------------
    public static function richiediRuolo(array $ruoli_ammessi): void
    {
        self::richiediLogin();
        if (!in_array(Auth::ruolo(), $ruoli_ammessi, true)) {
            http_response_code(403);
            include BASE_PATH . 'app/Views/layout/403.php';
            exit;
        }
    }

    // ----------------------------------------------------------
    //  Shortcut ruoli — per leggibilità nei controller
    // ----------------------------------------------------------
    public static function richiediSuperadmin(): void
    {
        self::richiediRuolo([RUOLO_SUPERADMIN]);
    }

    public static function richiediAdmin(): void
    {
        self::richiediRuolo([RUOLO_SUPERADMIN, RUOLO_ADMIN]);
    }

    public static function richiediClinico(): void
    {
        // Superadmin + Admin + Medico
        self::richiediRuolo([RUOLO_SUPERADMIN, RUOLO_ADMIN, RUOLO_MEDICO]);
    }

    public static function richiediSanitario(): void
    {
        // Tutti i ruoli operativi (medico + operatore)
        self::richiediRuolo([RUOLO_SUPERADMIN, RUOLO_ADMIN, RUOLO_MEDICO, RUOLO_UTENTE]);
    }

    // ----------------------------------------------------------
    //  Forza cambio password al primo accesso
    // ----------------------------------------------------------
    public static function verificaPasswordAggiornata(): void
    {
        self::richiediLogin();
        $utente = Auth::utente();
        if (!empty($utente['deve_cambiare_pwd'])) {
            $url_cambio = APP_URL . '/auth/cambia-password';
            if (strpos($_SERVER['REQUEST_URI'], '/auth/cambia-password') === false) {
                header('Location: ' . $url_cambio);
                exit;
            }
        }
    }

    // ----------------------------------------------------------
    //  Verifica accesso a una struttura specifica
    //  Uso: Middleware::richiediAccessoStruttura($id_struttura)
    // ----------------------------------------------------------
    public static function richiediAccessoStruttura(int $id_struttura): void
    {
        self::richiediLogin();
        if (!Auth::puo_accedere_struttura($id_struttura)) {
            http_response_code(403);
            include BASE_PATH . 'app/Views/layout/403.php';
            exit;
        }
    }
}
