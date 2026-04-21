<?php
// ============================================================
//  GIRA · app/Controllers/UbicazioneController.php
//  Gestisce: CRUD ubicazioni (area + subarea) per struttura
//  Solo admin e superadmin
// ============================================================

class UbicazioneController
{
    // ----------------------------------------------------------
    //  GET /ubicazioni?id_struttura=X
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediAdmin();

        $id_struttura = (int)($_GET['id_struttura'] ?? 0);

        // Se non specificata → usa struttura attiva in sessione
        if (!$id_struttura) {
            $id_struttura = (int)Auth::struttura_attiva();
        }

        // Se ancora non disponibile → fallback lista strutture (solo superadmin)
        if (!$id_struttura) {
            $strutture = self::_get_strutture_accessibili();
            if (count($strutture) === 1) {
                header('Location: ' . APP_URL . '/ubicazioni?id_struttura=' . $strutture[0]['id']);
                exit;
            }
            $page_title   = 'Reparti — GIRA';
            $current_page = 'ubicazioni';
            include VIEW_PATH . 'layout/header.php';
            include VIEW_PATH . 'ubicazioni/scegli_struttura.php';
            include VIEW_PATH . 'layout/footer.php';
            return;
        }

        $struttura = self::_trova_struttura($id_struttura);
        Middleware::richiediAccessoStruttura($id_struttura);

        $db = Database::getInstance();
        $ubicazioni = $db->prepare(
            'SELECT u.*,
                COUNT(d.id) AS tot_device
           FROM gir_ubicazione u
      LEFT JOIN gir_device d ON d.id_ubicazione = u.id AND d.attivo = 1
          WHERE u.id_struttura = :id
          GROUP BY u.id
          ORDER BY u.area, u.subarea'
        );
        $ubicazioni->execute([':id' => $id_struttura]);
        $ubicazioni = $ubicazioni->fetchAll();

        $page_title   = 'Reparti — ' . $struttura['ragione_sociale'];
        $current_page = 'ubicazioni';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'ubicazioni/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    private static function _get_strutture_accessibili(): array
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

    // ----------------------------------------------------------
    //  GET /ubicazioni/crea?id_struttura=X
    // ----------------------------------------------------------
    public static function crea(): void
    {
        Middleware::richiediAdmin();

        $id_struttura = (int)($_GET['id_struttura'] ?? 0);
        $struttura    = self::_trova_struttura($id_struttura);
        Middleware::richiediAccessoStruttura($id_struttura);

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Nuova ubicazione — ' . $struttura['ragione_sociale'];
        $current_page = 'ubicazioni';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'ubicazioni/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /ubicazioni/crea-post
    // ----------------------------------------------------------
    public static function creaPost(): void
    {
        Middleware::richiediAdmin();

        $id_struttura = (int)($_POST['id_struttura'] ?? 0);
        self::_trova_struttura($id_struttura);
        Middleware::richiediAccessoStruttura($id_struttura);

        $dati = self::_valida_form($id_struttura);
        if (!$dati) exit;

        Database::getInstance()->prepare(
            'INSERT INTO gir_ubicazione (id_struttura, area, subarea)
             VALUES (:id_struttura, :area, :subarea)'
        )->execute([
            ':id_struttura' => $id_struttura,
            ':area'         => $dati['area'],
            ':subarea'      => $dati['subarea'],
        ]);

        $_SESSION['successo'] = 'Ubicazione aggiunta.';
        header('Location: ' . APP_URL . '/ubicazioni?id_struttura=' . $id_struttura);
        exit;
    }

    // ----------------------------------------------------------
    //  GET /ubicazioni/modifica/{id}
    // ----------------------------------------------------------
    public static function modifica(?int $id): void
    {
        Middleware::richiediAdmin();
        $ubicazione   = self::_trova($id);
        $id_struttura = (int)$ubicazione['id_struttura'];
        $struttura    = self::_trova_struttura($id_struttura);
        Middleware::richiediAccessoStruttura($id_struttura);

        $errore    = $_SESSION['errore']    ?? null;
        $form_data = $_SESSION['form_data'] ?? (array)$ubicazione;
        unset($_SESSION['errore'], $_SESSION['form_data']);

        $page_title   = 'Modifica ubicazione — ' . $struttura['ragione_sociale'];
        $current_page = 'ubicazioni';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'ubicazioni/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /ubicazioni/modifica-post
    // ----------------------------------------------------------
    public static function modificaPost(): void
    {
        Middleware::richiediAdmin();

        $id           = (int)($_POST['id'] ?? 0);
        $ubicazione   = self::_trova($id);
        $id_struttura = (int)$ubicazione['id_struttura'];
        Middleware::richiediAccessoStruttura($id_struttura);

        $dati = self::_valida_form($id_struttura);
        if (!$dati) exit;

        Database::getInstance()->prepare(
            'UPDATE gir_ubicazione
                SET area = :area, subarea = :subarea
              WHERE id = :id'
        )->execute([
            ':area'    => $dati['area'],
            ':subarea' => $dati['subarea'],
            ':id'      => $id,
        ]);

        $_SESSION['successo'] = 'Ubicazione aggiornata.';
        header('Location: ' . APP_URL . '/ubicazioni?id_struttura=' . $id_struttura);
        exit;
    }

    // ----------------------------------------------------------
    //  GET /ubicazioni/elimina/{id}
    // ----------------------------------------------------------
    public static function elimina(?int $id): void
    {
        Middleware::richiediAdmin();
        $ubicazione   = self::_trova($id);
        $id_struttura = (int)$ubicazione['id_struttura'];
        Middleware::richiediAccessoStruttura($id_struttura);

        // Verifica che non ci siano device assegnati
        $tot = Database::getInstance()->prepare(
            'SELECT COUNT(*) FROM gir_device WHERE id_ubicazione = :id AND attivo = 1'
        );
        $tot->execute([':id' => $id]);
        if ((int)$tot->fetchColumn() > 0) {
            $_SESSION['errore'] = 'Impossibile eliminare: ci sono device assegnati a questa ubicazione.';
            header('Location: ' . APP_URL . '/ubicazioni?id_struttura=' . $id_struttura);
            exit;
        }

        Database::getInstance()->prepare(
            'DELETE FROM gir_ubicazione WHERE id = :id'
        )->execute([':id' => $id]);

        $_SESSION['successo'] = 'Ubicazione eliminata.';
        header('Location: ' . APP_URL . '/ubicazioni?id_struttura=' . $id_struttura);
        exit;
    }


    // ----------------------------------------------------------
    //  HELPERS PRIVATI
    // ----------------------------------------------------------

    private static function _trova(?int $id): array
    {
        if (!$id) {
            $_SESSION['errore'] = 'ID non valido.';
            header('Location: ' . APP_URL . '/strutture');
            exit;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM gir_ubicazione WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $ubicazione = $stmt->fetch();

        if (!$ubicazione) {
            $_SESSION['errore'] = 'Ubicazione non trovata.';
            header('Location: ' . APP_URL . '/strutture');
            exit;
        }
        return $ubicazione;
    }

    private static function _trova_struttura(int $id): array
    {
        if (!$id) {
            $_SESSION['errore'] = 'Struttura non specificata.';
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

    private static function _valida_form(int $id_struttura): ?array
    {
        $area    = trim($_POST['area']    ?? '');
        $subarea = trim($_POST['subarea'] ?? '');

        if (empty($area)) {
            $_SESSION['errore']    = 'Il campo Area è obbligatorio.';
            $_SESSION['form_data'] = $_POST;
            $id = (int)($_POST['id'] ?? 0);
            header('Location: ' . APP_URL . '/ubicazioni/' .
                ($id ? 'modifica/' . $id : 'crea?id_struttura=' . $id_struttura));
            exit;
        }

        return [
            'area'    => $area,
            'subarea' => $subarea ?: null,
        ];
    }
}
