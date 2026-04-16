<?php
// ============================================================
//  GIRA · app/Controllers/SoglieController.php
//  Permette all'admin di modificare le soglie della struttura
//  entro i limiti definiti dal superadmin
// ============================================================

class SoglieController
{
    // ----------------------------------------------------------
    //  GET /soglie
    // ----------------------------------------------------------
    public static function index(): void
    {
        Middleware::richiediAdmin();
        Middleware::verificaPasswordAggiornata();

        $db           = Database::getInstance();
        $id_struttura = Auth::struttura_attiva();

        if (!$id_struttura) {
            $_SESSION['errore'] = 'Nessuna struttura attiva.';
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        $struttura = $db->prepare('SELECT * FROM gir_struttura WHERE id = :id LIMIT 1');
        $struttura->execute([':id' => $id_struttura]);
        $struttura = $struttura->fetch();

        $soglie = $db->prepare('SELECT * FROM gir_soglie WHERE id_struttura = :id LIMIT 1');
        $soglie->execute([':id' => $id_struttura]);
        $soglie = $soglie->fetch();

        // Fallback ai default se non esistono soglie
        if (!$soglie) {
            $soglie = [
                'soglia_arancio_min' => ALERT_ARANCIO_MIN,
                'soglia_rosso_min'   => ALERT_ROSSO_MIN,
                'silenzio_da'        => 22,
                'silenzio_a'         => 7,
                'arancio_min_min'    => 10,
                'arancio_min_max'    => 30,
                'rosso_min_min'      => 20,
                'rosso_min_max'      => 60,
                'silenzio_da_min'    => 20,
                'silenzio_da_max'    => 23,
                'silenzio_a_min'     => 5,
                'silenzio_a_max'     => 9,
            ];
        }

        $errore   = $_SESSION['errore']   ?? null;
        $successo = $_SESSION['successo'] ?? null;
        unset($_SESSION['errore'], $_SESSION['successo']);

        $page_title   = 'Soglie alert — GIRA';
        $current_page = 'soglie';
        include VIEW_PATH . 'layout/header.php';
        include VIEW_PATH . 'soglie/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    // ----------------------------------------------------------
    //  POST /soglie/salva
    // ----------------------------------------------------------
    public static function salva(): void
    {
        Middleware::richiediAdmin();

        $db           = Database::getInstance();
        $id_struttura = Auth::struttura_attiva();

        if (!$id_struttura) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Carica i limiti definiti dal superadmin
        $soglie_db = $db->prepare('SELECT * FROM gir_soglie WHERE id_struttura = :id LIMIT 1');
        $soglie_db->execute([':id' => $id_struttura]);
        $soglie_db = $soglie_db->fetch();

        $arancio_min_min  = (int)($soglie_db['arancio_min_min']  ?? 10);
        $arancio_min_max  = (int)($soglie_db['arancio_min_max']  ?? 30);
        $rosso_min_min    = (int)($soglie_db['rosso_min_min']    ?? 20);
        $rosso_min_max    = (int)($soglie_db['rosso_min_max']    ?? 60);
        $silenzio_da_min  = (int)($soglie_db['silenzio_da_min']  ?? 20);
        $silenzio_da_max  = (int)($soglie_db['silenzio_da_max']  ?? 23);
        $silenzio_a_min   = (int)($soglie_db['silenzio_a_min']   ?? 5);
        $silenzio_a_max   = (int)($soglie_db['silenzio_a_max']   ?? 9);

        $arancio     = (int)($_POST['soglia_arancio_min'] ?? ALERT_ARANCIO_MIN);
        $rosso       = (int)($_POST['soglia_rosso_min']   ?? ALERT_ROSSO_MIN);
        $silenzio_da = (int)($_POST['silenzio_da']        ?? 22);
        $silenzio_a  = (int)($_POST['silenzio_a']         ?? 7);

        // Validazione range soglie
        if ($arancio < $arancio_min_min || $arancio > $arancio_min_max) {
            $_SESSION['errore'] = "Soglia arancio deve essere tra {$arancio_min_min} e {$arancio_min_max} minuti.";
            header('Location: ' . APP_URL . '/soglie');
            exit;
        }
        if ($rosso < $rosso_min_min || $rosso > $rosso_min_max) {
            $_SESSION['errore'] = "Soglia rossa deve essere tra {$rosso_min_min} e {$rosso_min_max} minuti.";
            header('Location: ' . APP_URL . '/soglie');
            exit;
        }

        // Validazione overlap
        if ($rosso < $arancio + 5) {
            $_SESSION['errore'] = 'La soglia rossa deve essere almeno 5 minuti maggiore della soglia arancio.';
            header('Location: ' . APP_URL . '/soglie');
            exit;
        }

        // Validazione silenzio notturno
        if ($silenzio_da < $silenzio_da_min || $silenzio_da > $silenzio_da_max) {
            $_SESSION['errore'] = "Inizio silenzio deve essere tra le {$silenzio_da_min}:00 e le {$silenzio_da_max}:00.";
            header('Location: ' . APP_URL . '/soglie');
            exit;
        }
        if ($silenzio_a < $silenzio_a_min || $silenzio_a > $silenzio_a_max) {
            $_SESSION['errore'] = "Fine silenzio deve essere tra le {$silenzio_a_min}:00 e le {$silenzio_a_max}:00.";
            header('Location: ' . APP_URL . '/soglie');
            exit;
        }

        $db->prepare(
            'UPDATE gir_soglie
                SET soglia_arancio_min = :ar,
                    soglia_rosso_min   = :ro,
                    silenzio_da        = :sda,
                    silenzio_a         = :sa
              WHERE id_struttura = :id'
        )->execute([
            ':ar'  => $arancio,
            ':ro'  => $rosso,
            ':sda' => $silenzio_da,
            ':sa'  => $silenzio_a,
            ':id'  => $id_struttura,
        ]);

        $_SESSION['successo'] = 'Soglie aggiornate.';
        header('Location: ' . APP_URL . '/soglie');
        exit;
    }
}
