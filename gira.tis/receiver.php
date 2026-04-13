<?php
// ============================================================
//  GIRA · receiver.php
//  Legge i pacchetti HTTP POST in arrivo dal gateway
//  e li salva in un file di log leggibile.
//  Da usare SOLO in fase di sviluppo/debug.
// ============================================================

$log_file = __DIR__ . '/gateway_log.txt';

// Legge il body grezzo
$raw = file_get_contents('php://input');

// Prova a decodificare come JSON
$decoded = json_decode($raw, true);

// Costruisce la riga di log
$entry  = "============================================================\n";
$entry .= "DATA/ORA : " . date('Y-m-d H:i:s') . "\n";
$entry .= "IP       : " . ($_SERVER['REMOTE_ADDR'] ?? 'n/d') . "\n";
$entry .= "METHOD   : " . ($_SERVER['REQUEST_METHOD'] ?? 'n/d') . "\n";
$entry .= "CONTENT  : " . ($_SERVER['CONTENT_TYPE'] ?? 'n/d') . "\n";
$entry .= "------------------------------------------------------------\n";
$entry .= "RAW BODY :\n" . $raw . "\n";

if ($decoded !== null) {
    $entry .= "------------------------------------------------------------\n";
    $entry .= "DECODED  :\n" . print_r($decoded, true);
} else {
    $entry .= "------------------------------------------------------------\n";
    $entry .= "DECODED  : (non è JSON valido)\n";
}

$entry .= "============================================================\n\n";

// Scrive nel log (append)
file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);

// Risponde al gateway con 200 OK
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
