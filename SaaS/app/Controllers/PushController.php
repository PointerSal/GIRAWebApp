<?php
// ============================================================
//  GIRA · app/Controllers/PushController.php
//  Gestisce: salvataggio e cancellazione subscription push
//  Endpoint: POST /push/subscribe
//            POST /push/unsubscribe
// ============================================================

class PushController
{
    // ----------------------------------------------------------
    //  POST /push/subscribe
    //  Body JSON: { endpoint, keys: { p256dh, auth } }
    // ----------------------------------------------------------
    public static function subscribe(): void
    {
        Middleware::richiediLogin();

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (
            empty($data['endpoint']) ||
            empty($data['keys']['p256dh']) ||
            empty($data['keys']['auth'])
        ) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'payload incompleto']);
            exit;
        }

        $db        = Database::getInstance();
        $id_utente = Auth::id();

        // Upsert — se l'endpoint esiste già aggiorna le chiavi
        $db->prepare(
            "INSERT INTO gir_push_subscription
                (id_utente, endpoint, p256dh, auth, creata_il)
             VALUES
                (:uid, :ep, :p256dh, :auth, NOW())
             ON DUPLICATE KEY UPDATE
                p256dh     = VALUES(p256dh),
                auth       = VALUES(auth),
                creata_il  = NOW()"
        )->execute([
            ':uid'    => $id_utente,
            ':ep'     => $data['endpoint'],
            ':p256dh' => $data['keys']['p256dh'],
            ':auth'   => $data['keys']['auth'],
        ]);

        // Assicura che le preferenze esistano (create alla registrazione utente,
        // ma per sicurezza le creiamo qui se mancanti)
        $db->prepare(
            "INSERT IGNORE INTO gir_notifica_preferenze (id_utente) VALUES (:uid)"
        )->execute([':uid' => $id_utente]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    // ----------------------------------------------------------
    //  POST /push/unsubscribe
    //  Body JSON: { endpoint }
    // ----------------------------------------------------------
    public static function unsubscribe(): void
    {
        Middleware::richiediLogin();

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (empty($data['endpoint'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'endpoint mancante']);
            exit;
        }

        Database::getInstance()->prepare(
            'DELETE FROM gir_push_subscription
              WHERE endpoint = :ep AND id_utente = :uid'
        )->execute([
            ':ep'  => $data['endpoint'],
            ':uid' => Auth::id(),
        ]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
