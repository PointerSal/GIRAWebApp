<?php
// ============================================================
//  GIRA · cron/pulizia_raw.php
//  Cancella i dati grezzi in gir_raw più vecchi di 24h.
//  Serve a mantenere la tabella leggera — i dati grezzi
//  sono solo per calibrazione, non per lo storico.
//
//  Frequenza consigliata: una volta al giorno (es. alle 3:00)
//  Configurazione cPanel:
//  0 3 * * * /usr/bin/php /home/klmkejnd/tis.gira/cron/pulizia_raw.php
// ============================================================

$lock = sys_get_temp_dir() . '/gira_pulizia_raw.lock';
if (file_exists($lock)) exit;
file_put_contents($lock, '1');

require_once __DIR__ . '/../../SaaS/gira/app/Config/config.php';
require_once __DIR__ . '/../../SaaS/gira/app/Core/Database.php';

try {
    $db = Database::getInstance();

    $stmt = $db->query(
        "DELETE FROM gir_raw
          WHERE ricevuto_alle < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );

    $eliminati = $stmt->rowCount();
    echo date('Y-m-d H:i:s') . " — Pulizia gir_raw: {$eliminati} righe eliminate.\n";
} catch (\Throwable $e) {
    error_log('GIRA cron pulizia_raw error: ' . $e->getMessage());
} finally {
    unlink($lock);
}
