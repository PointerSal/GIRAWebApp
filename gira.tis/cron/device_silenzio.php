<?php
// ============================================================
//  GIRA · cron/device_silenzio.php
//  Controlla device che non mandano dati da troppo tempo
//  e genera alert OFFLINE se necessario.
//
//  Frequenza consigliata: ogni 5 minuti
//  Configurazione cPanel:
//  */5 * * * * /usr/local/bin/php /home/klmkejnd/tis.gira/cron/device_silenzio.php >/dev/null 2>&1
//  */5 * * * * /usr/local/bin/php /home/klmkejnd/tis.gira/cron/device_silenzio.php >> /home/klmkejnd/tis.gira/cron/cron_log.txt 2>&1
// ============================================================


// Evita esecuzioni sovrapposte
$lock = sys_get_temp_dir() . '/gira_device_silenzio.lock';
if (file_exists($lock)) exit;
file_put_contents($lock, '1');

require_once __DIR__ . '/../../SaaS/gira/app/Config/config.php';
require_once __DIR__ . '/../../SaaS/gira/app/Core/Database.php';
require_once __DIR__ . '/../../SaaS/gira/vendor/autoload.php';
require_once __DIR__ . '/../../SaaS/gira/app/Core/NotificationService.php';

try {
    $db = Database::getInstance();

    // Device attivi che non mandano dati da più di 10 minuti
    $silenziosi = $db->query(
        "SELECT d.id, d.id_struttura, ds.ultimo_contatto,
                TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) AS minuti_silenzio
           FROM gir_device d
           JOIN gir_device_stato ds ON ds.id_device = d.id
          WHERE d.attivo = 1
            AND TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) > 10"
    )->fetchAll();

    foreach ($silenziosi as $d) {
        // Apri alert OFFLINE solo se non ce n'è già uno aperto
        $esiste = $db->prepare(
            "SELECT id FROM gir_alert
              WHERE id_device = :id AND tipo = 'OFFLINE' AND chiuso_alle IS NULL
              LIMIT 1"
        );
        $esiste->execute([':id' => $d['id']]);
        if ($esiste->fetch()) continue;

        $db->prepare(
            "INSERT INTO gir_alert (id_device, tipo, aperto_alle)
             VALUES (:id, 'OFFLINE', NOW())"
        )->execute([':id' => $d['id']]);

        // Recupera label e ubicazione per la notifica push
        $info = $db->prepare(
            "SELECT d.label, d.mac, u.area, u.subarea
               FROM gir_device d
          LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
              WHERE d.id = :id LIMIT 1"
        );
        $info->execute([':id' => $d['id']]);
        $info = $info->fetch();

        NotificationService::invia(
            $db,
            (int)$d['id'],
            'OFFLINE',
            $info['label'] ?? $info['mac'] ?? null,
            $info['area']    ?? null,
            $info['subarea'] ?? null
        );

        echo date('Y-m-d H:i:s') . " — Alert OFFLINE aperto per device {$d['id']} "
            . "(silenzioso da {$d['minuti_silenzio']} min)\n";
    }

    // Chiudi alert OFFLINE per device tornati online
    // (quelli che hanno mandato dati negli ultimi 10 minuti)
    $db->query(
        "UPDATE gir_alert a
            JOIN gir_device_stato ds ON ds.id_device = a.id_device
            SET a.chiuso_alle = NOW()
          WHERE a.tipo = 'OFFLINE'
            AND a.chiuso_alle IS NULL
            AND TIMESTAMPDIFF(MINUTE, ds.ultimo_contatto, NOW()) <= 10"
    );
} catch (\Throwable $e) {
    error_log('GIRA cron device_silenzio error: ' . $e->getMessage());
} finally {
    unlink($lock);
}
