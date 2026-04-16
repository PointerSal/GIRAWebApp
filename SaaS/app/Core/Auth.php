<?php
// ============================================================
//  GIRA · app/Core/Auth.php
//  Gestisce: login, logout, sessione, remember token,
//            reset password, cambio password
//
//  Dipendenze:
//  - Database.php   (singleton PDO)
//  - Mailer.php     (wrapper mail)
//  - config.php     (costanti RUOLO_*, APP_URL, ecc.)
//
//  In config.php aggiungere:
//      define('SUPERADMIN_EMAIL', 'tua@email.com');
//      define('SUPERADMIN_NOME',  'Piero');
// ============================================================

class Auth
{
    // ----------------------------------------------------------
    //  LOGIN
    //  Blocchi in ordine:
    //  1. Credenziali errate
    //  2. Utente disattivato
    //  3. Struttura sospesa (solo per non-superadmin)
    //  4. Piano scaduto (solo per non-superadmin)
    // ----------------------------------------------------------
    public static function login(string $mail, string $password): array
    {
        $mail = strtolower(trim($mail));
        $db   = Database::getInstance();

        // Carica utente + struttura (se presente) + subscription
        $stmt = $db->prepare(
            'SELECT u.*,
                    s.ragione_sociale AS struttura_nome,
                    s.attiva          AS struttura_attiva,
                    sub.stato         AS sub_stato,
                    sub.fine_il       AS sub_fine
               FROM gir_utenti u
          LEFT JOIN gir_utente_struttura us  ON us.id_utente = u.id
          LEFT JOIN gir_struttura s          ON s.id = us.id_struttura
          LEFT JOIN gir_subscription sub     ON sub.id_struttura = s.id
              WHERE u.mail = :mail
              LIMIT 1'
        );
        $stmt->execute([':mail' => $mail]);
        $utente = $stmt->fetch();

        // 1. Credenziali
        if (!$utente || !password_verify($password, $utente['password_hash'])) {
            return ['ok' => false, 'errore' => 'Credenziali non valide.'];
        }

        // 2. Utente disattivato (doppio check — già in WHERE ma per sicurezza)
        if (!(int)$utente['attivo']) {
            return ['ok' => false, 'errore' => 'Il tuo account è stato disattivato. Contatta il tuo admin.'];
        }

        // Superadmin: salta i controlli struttura/piano
        if ((int)$utente['id_ruolo'] !== RUOLO_SUPERADMIN) {

            // 3. Struttura sospesa
            if (isset($utente['struttura_attiva']) && !(int)$utente['struttura_attiva']) {
                return [
                    'ok'     => false,
                    'errore' => 'La struttura associata al tuo account è sospesa. Contatta il supporto.',
                ];
            }

            // 4. Subscription scaduta
            if (
                isset($utente['sub_stato']) &&
                $utente['sub_stato'] === 'SCADUTA'
            ) {
                return [
                    'ok'     => false,
                    'errore' => 'Il piano della tua struttura è scaduto. Contatta il responsabile.',
                ];
            }
        }

        // ✅ Login OK
        $db->prepare('UPDATE gir_utenti SET ultimo_accesso = NOW() WHERE id = :id')
            ->execute([':id' => $utente['id']]);

        unset($utente['password_hash']);
        $_SESSION['utente'] = $utente;

        return ['ok' => true, 'errore' => ''];
    }


    // ----------------------------------------------------------
    //  LOGOUT
    // ----------------------------------------------------------
    public static function logout(): void
    {
        if (isset($_COOKIE['remember_token'])) {
            self::_rimuovi_remember_token($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        session_unset();
        session_destroy();
    }


    // ----------------------------------------------------------
    //  REMEMBER TOKEN — "ricordami" (durata 48 ore, scorrevole)
    // ----------------------------------------------------------
    public static function crea_remember_token(int $utente_id): void
    {
        $token    = bin2hex(random_bytes(32));
        $scadenza = date('Y-m-d H:i:s', time() + 48 * 3600);
        $db       = Database::getInstance();

        $db->prepare('DELETE FROM gir_remember_tokens WHERE id_utente = :uid')
            ->execute([':uid' => $utente_id]);

        $db->prepare(
            'INSERT INTO gir_remember_tokens (id_utente, token, scadenza)
             VALUES (:uid, :tok, :scad)'
        )->execute([':uid' => $utente_id, ':tok' => $token, ':scad' => $scadenza]);

        setcookie('remember_token', $token, time() + 48 * 3600, '/', '', true, true);
    }

    public static function controlla_remember_token(): void
    {
        if (isset($_SESSION['utente']))        return;
        if (empty($_COOKIE['remember_token'])) return;

        $token = $_COOKIE['remember_token'];
        $db    = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT u.*,
                    s.ragione_sociale AS struttura_nome,
                    s.attiva          AS struttura_attiva
               FROM gir_remember_tokens rt
               JOIN gir_utenti u          ON u.id = rt.id_utente
          LEFT JOIN gir_utente_struttura us ON us.id_utente = u.id
          LEFT JOIN gir_struttura s        ON s.id = us.id_struttura
              WHERE rt.token = :tok
                AND rt.scadenza > NOW()
                AND u.attivo = 1
              LIMIT 1'
        );
        $stmt->execute([':tok' => $token]);
        $utente = $stmt->fetch();

        if (!$utente) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return;
        }

        // Rinnova token scorrevole
        self::_rimuovi_remember_token($token);
        unset($utente['password_hash']);
        $_SESSION['utente'] = $utente;
        self::crea_remember_token((int)$utente['id']);
    }

    private static function _rimuovi_remember_token(string $token): void
    {
        Database::getInstance()
            ->prepare('DELETE FROM gir_remember_tokens WHERE token = :tok')
            ->execute([':tok' => $token]);
    }


    // ----------------------------------------------------------
    //  HELPERS SESSIONE
    // ----------------------------------------------------------
    public static function utente(): ?array
    {
        return $_SESSION['utente'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['utente']['id'])
            ? (int)$_SESSION['utente']['id']
            : null;
    }

    public static function ruolo(): int
    {
        return (int)($_SESSION['utente']['id_ruolo'] ?? 99);
    }

    public static function isLogged(): bool
    {
        return isset($_SESSION['utente']);
    }

    public static function isSuperadmin(): bool
    {
        return self::ruolo() === RUOLO_SUPERADMIN;
    }

    public static function isAdmin(): bool
    {
        // Admin o superiore
        return self::ruolo() <= RUOLO_ADMIN;
    }

    public static function isMedico(): bool
    {
        return self::ruolo() === RUOLO_MEDICO;
    }

    public static function isOperatore(): bool
    {
        return self::ruolo() === RUOLO_UTENTE;
    }

    /**
     * Verifica se l'utente loggato può accedere a una struttura.
     * Superadmin → sempre sì.
     * Altri → solo se in gir_utente_struttura.
     */
    public static function puo_accedere_struttura(int $id_struttura): bool
    {
        if (self::isSuperadmin()) return true;

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT 1 FROM gir_utente_struttura
              WHERE id_utente = :uid AND id_struttura = :sid
              LIMIT 1'
        );
        $stmt->execute([
            ':uid' => self::id(),
            ':sid' => $id_struttura,
        ]);
        return (bool)$stmt->fetch();
    }

    /**
     * Restituisce gli id delle strutture accessibili dall'utente loggato.
     * Superadmin → tutte.
     */
    public static function strutture_accessibili(): array
    {
        $db = Database::getInstance();

        if (self::isSuperadmin()) {
            $stmt = $db->prepare('SELECT id FROM gir_struttura WHERE attiva = 1');
            $stmt->execute();
        } else {
            $stmt = $db->prepare(
                'SELECT us.id_struttura AS id
                   FROM gir_utente_struttura us
                   JOIN gir_struttura s ON s.id = us.id_struttura
                  WHERE us.id_utente = :uid AND s.attiva = 1'
            );
            $stmt->execute([':uid' => self::id()]);
        }

        return array_column($stmt->fetchAll(), 'id');
    }


    // ----------------------------------------------------------
    //  RESET PASSWORD (admin per i propri utenti)
    // ----------------------------------------------------------
    public static function reset_password(int $utente_id, string $nuova_password): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id_ruolo FROM gir_utenti WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $utente_id]);
        $target = $stmt->fetch();

        if (!$target) {
            throw new \RuntimeException('Utente non trovato.');
        }

        // Un admin non può resettare password di altri admin o superiori
        if (!self::isSuperadmin()) {
            if ((int)$target['id_ruolo'] <= RUOLO_ADMIN) {
                throw new \RuntimeException('Non puoi resettare la password di un admin.');
            }
            // Verifica che l'utente target appartenga a una struttura dell'admin
            $condivisa = $db->prepare(
                'SELECT 1 FROM gir_utente_struttura us1
                   JOIN gir_utente_struttura us2 ON us2.id_struttura = us1.id_struttura
                  WHERE us1.id_utente = :admin AND us2.id_utente = :target
                  LIMIT 1'
            );
            $condivisa->execute([':admin' => self::id(), ':target' => $utente_id]);
            if (!$condivisa->fetch()) {
                throw new \RuntimeException('Non puoi modificare utenti di un\'altra struttura.');
            }
        }

        $db->prepare(
            'UPDATE gir_utenti
                SET password_hash     = :hash,
                    deve_cambiare_pwd = 1
              WHERE id = :id'
        )->execute([
            ':hash' => password_hash($nuova_password, PASSWORD_BCRYPT),
            ':id'   => $utente_id,
        ]);
    }


    // ----------------------------------------------------------
    //  CAMBIO PASSWORD (utente su se stesso)
    // ----------------------------------------------------------
    public static function cambia_password(int $utente_id, string $vecchia, string $nuova): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT password_hash FROM gir_utenti WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $utente_id]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($vecchia, $row['password_hash'])) {
            throw new \RuntimeException('La password attuale non è corretta.');
        }

        $db->prepare(
            'UPDATE gir_utenti
                SET password_hash     = :hash,
                    deve_cambiare_pwd = 0
              WHERE id = :id'
        )->execute([
            ':hash' => password_hash($nuova, PASSWORD_BCRYPT),
            ':id'   => $utente_id,
        ]);

        $_SESSION['utente']['deve_cambiare_pwd'] = 0;
    }

    /**
     * Restituisce l'ID della struttura attiva in sessione.
     * Se non impostata, usa la prima struttura accessibile.
     */
    public static function struttura_attiva(): int
    {
        if (self::isSuperadmin()) return 0; // superadmin → nessun filtro

        // Se già in sessione → usa quella
        if (!empty($_SESSION['struttura_attiva'])) {
            return (int)$_SESSION['struttura_attiva'];
        }

        // Altrimenti prendi la prima disponibile
        $ids = self::strutture_accessibili();
        if (empty($ids)) return 0;

        $_SESSION['struttura_attiva'] = $ids[0];
        return (int)$ids[0];
    }

    public static function set_struttura_attiva(int $id): void
    {
        // Verifica che l'utente abbia accesso a questa struttura
        if (!self::puo_accedere_struttura($id)) return;
        $_SESSION['struttura_attiva'] = $id;
    }
}
