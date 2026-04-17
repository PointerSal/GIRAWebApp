<?php
// ============================================================
//  GIRA · ingest_log.php
//  Versione temporanea di debug — salva i primi 10 payload
//  raw su file log senza nessuna decodifica.
//  DA RIMUOVERE dopo l'analisi!
// ============================================================

$log_file  = __DIR__ . '/ingest_raw.log';
$max_pacchetti = 10;

// Conta quanti pacchetti abbiamo già salvato
$count = 0;
if (file_exists($log_file)) {
    $content = file_get_contents($log_file);
    $count   = substr_count($content, '=== PACCHETTO');
}

// Se abbiamo già raggiunto il limite → rispondi OK e stop
if ($count >= $max_pacchetti) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'note' => 'log completo']);
    exit;
}

// Leggi body grezzo
$raw = file_get_contents('php://input');

// Salva su file
$separatore = str_repeat('=', 60);
$entry = "\n{$separatore}\n";
$entry .= "=== PACCHETTO " . ($count + 1) . " / {$max_pacchetti} ===\n";
$entry .= "=== " . date('Y-m-d H:i:s') . " ===\n";
$entry .= "{$separatore}\n\n";
$entry .= "RAW BODY:\n{$raw}\n\n";

// Prova anche a decodificare JSON per leggibilità
$data = json_decode($raw, true);
if (is_array($data)) {
    $entry .= "JSON DECODED:\n" . print_r($data, true) . "\n";
}

file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);

// Risposta al gateway
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'saved' => $count + 1]);
exit;
