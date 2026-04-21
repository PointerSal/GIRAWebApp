<?php
// ============================================================
//  GIRA · app/Core/NotificationService.php
//  Invia notifiche push Web Push (VAPID) agli utenti
//  della struttura per cui è stato generato un alert.
//
//  Dipende da: minishlink/web-push (via Composer)
//  Chiamato da: ingest.php dopo apri_alert_se_assente()
// ============================================================

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class NotificationService
{
    // Mappa tipo alert → campo preferenza nella tabella
    private static array $campo_pref = [
        'ROSSO'    => 'alert_rosso',
        'ARANCIO'  => 'alert_arancio',
        'BATTERIA' => 'alert_batteria',
        'OFFLINE'  => 'alert_offline',
        'PULSANTE' => 'alert_rosso', // SOS usa stesso flag del rosso
    ];

    // ─────────────────────────────────────────────────────────
    //  Metodo principale — chiamato da ingest.php
    //  $id_device   → id del device che ha generato l'alert
    //  $tipo        → tipo alert (ROSSO, OFFLINE, ecc.)
    //  $label       → label del device (es. "Letto 3")
    //  $area        → area ubicazione (es. "Piano Terra")
    //  $subarea     → subarea ubicazione (es. "Stanza 1")
    // ─────────────────────────────────────────────────────────
    public static function invia(
        PDO    $db,
        int    $id_device,
        string $tipo,
        ?string $label   = null,
        ?string $area    = null,
        ?string $subarea = null
    ): void {
        // Verifica dipendenza Composer
        if (!class_exists(WebPush::class)) return;

        $campo = self::$campo_pref[$tipo] ?? null;
        if (!$campo) return;

        // Recupera id_struttura del device
        $stmt = $db->prepare('SELECT id_struttura FROM gir_device WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id_device]);
        $row = $stmt->fetch();
        if (!$row) return;
        $id_struttura = (int)$row['id_struttura'];

        // Recupera subscription + preferenze degli utenti della struttura
        // che hanno push attiva e il flag specifico per questo tipo di alert
        $stmt = $db->prepare(
            "SELECT ps.endpoint, ps.p256dh, ps.auth
               FROM gir_push_subscription ps
               JOIN gir_utente_struttura us  ON us.id_utente = ps.id_utente
               JOIN gir_notifica_preferenze np ON np.id_utente = ps.id_utente
              WHERE us.id_struttura = :sid
                AND np.push_attiva = 1
                AND np.$campo = 1"
        );
        $stmt->execute([':sid' => $id_struttura]);
        $subscriptions = $stmt->fetchAll();

        if (empty($subscriptions)) return;

        // Costruisci payload notifica
        $device_str = $label ?? 'Device';
        if ($area)    $device_str .= ' · ' . $area;
        if ($subarea) $device_str .= ' · ' . $subarea;

        $titoli = [
            'ROSSO'    => '🔴 Alert rosso',
            'ARANCIO'  => '🟠 Alert arancio',
            'BATTERIA' => '🔋 Batteria scarica',
            'OFFLINE'  => '📡 Device offline',
            'PULSANTE' => '🆘 SOS — Emergenza',
        ];

        // Recupera nome struttura
        $stmt_s = $db->prepare('SELECT ragione_sociale FROM gir_struttura WHERE id = :id LIMIT 1');
        $stmt_s->execute([':id' => $id_struttura]);
        $nome_struttura = $stmt_s->fetchColumn() ?: '';

        $payload = json_encode([
            'title'               => $titoli[$tipo] ?? 'GIRA Alert',
            'body'                => ($nome_struttura ? $nome_struttura . ' · ' : '') . $device_str,
            'icon'                => '/assets/img/gira_192x192.png',
            'badge'               => '/assets/img/gira_192x192.png',
            'tag'                 => 'gira-' . strtolower($tipo),
            'url'                 => '/alert',
            'requireInteraction'  => in_array($tipo, ['PULSANTE', 'ROSSO']),
        ]);

        // Inizializza WebPush
        $auth = [
            'VAPID' => [
                'subject'    => VAPID_SUBJECT,
                'publicKey'  => VAPID_PUBLIC,
                'privateKey' => VAPID_PRIVATE,
            ],
        ];

        try {
            $webPush = new WebPush($auth);
            $webPush->setReuseVAPIDHeaders(true);

            foreach ($subscriptions as $sub) {
                $subscription = Subscription::create([
                    'endpoint'        => $sub['endpoint'],
                    'keys'            => [
                        'p256dh' => $sub['p256dh'],
                        'auth'   => $sub['auth'],
                    ],
                ]);
                $webPush->queueNotification($subscription, $payload);
            }

            // Invia tutto in batch e gestisci subscription scadute
            foreach ($webPush->flush() as $report) {
                if (!$report->isSuccess()) {
                    // Subscription non più valida → rimuovila
                    if ($report->isSubscriptionExpired()) {
                        $db->prepare(
                            'DELETE FROM gir_push_subscription WHERE endpoint = :ep'
                        )->execute([':ep' => $report->getRequest()->getUri()->__toString()]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Errore push non deve bloccare ingest — fallisce silenziosamente
            error_log('GIRA NotificationService error: ' . $e->getMessage());
        }
    }
}
