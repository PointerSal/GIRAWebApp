<?php
// ============================================================
// GIRA · ingest.php
// Riceve payload JSON dal gateway, decodifica ADV BLE e payload custom GIRA,
// logga su gateway_log.txt e risponde JSON.
// ============================================================

declare(strict_types=1);

// --- Config log file (stesso usato da viewer.php) ---
$log_file = __DIR__ . '/gateway_log.txt';

// --- Legge body grezzo ---
$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    respond_json(400, ['ok' => false, 'error' => 'empty body']);
}

// --- Decodifica JSON ---
$data = json_decode($raw, true);
if (!is_array($data)) {
    log_entry($log_file, $raw, ['error' => 'invalid json']);
    respond_json(400, ['ok' => false, 'error' => 'invalid json']);
}

// --- Validazione minima ---
$required = ['v', 'mid', 'time', 'ip', 'mac', 'devices', 'rssi'];
foreach ($required as $k) {
    if (!array_key_exists($k, $data)) {
        log_entry($log_file, $raw, ['error' => "missing $k"]);
        respond_json(400, ['ok' => false, 'error' => "missing $k"]);
    }
}
if (!is_array($data['devices'])) {
    log_entry($log_file, $raw, ['error' => 'devices must be array']);
    respond_json(400, ['ok' => false, 'error' => 'devices must be array']);
}

$gateway = [
    'v'    => (int)$data['v'],
    'mid'  => (int)$data['mid'],
    'time' => (int)$data['time'],
    'ip'   => (string)$data['ip'],
    'mac'  => normalize_mac((string)$data['mac']),
    'rssi' => (int)$data['rssi'],
];

// --- Decodifica devices ---
$decodedDevices = [];
foreach ($data['devices'] as $i => $dev) {
    // atteso: [0, "D1A3...", -64, "020106..."]
    if (!is_array($dev) || count($dev) < 4) {
        $decodedDevices[] = ['index' => $i, 'error' => 'invalid device tuple'];
        continue;
    }

    $dtype = (int)$dev[0];
    $dmac  = normalize_mac((string)$dev[1]);
    $drssi = (int)$dev[2];
    $hex   = strtoupper(trim((string)$dev[3]));

    $bytes = hex_to_bytes($hex);
    if ($bytes === null) {
        $decodedDevices[] = [
            'index' => $i,
            'type'  => $dtype,
            'mac'   => $dmac,
            'rssi'  => $drssi,
            'hex'   => $hex,
            'error' => 'invalid hex'
        ];
        continue;
    }

    $adv = parse_ble_adv($bytes);

    $decodedDevices[] = [
        'index' => $i,
        'type'  => $dtype,
        'mac'   => $dmac,
        'rssi'  => $drssi,
        'hex'   => $hex,
        'adv'   => $adv,
    ];
}

$out = [
    'gateway' => $gateway,
    'devices' => $decodedDevices,
];

// --- Log su file (append) ---
log_entry($log_file, $raw, $out);

// --- Risposta al gateway ---
respond_json(200, ['ok' => true, 'decoded' => $out]);

// ============================================================
// Helpers
// ============================================================

function respond_json(int $code, array $payload): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function log_entry(string $log_file, string $raw, array $decoded): void
{
    $entry  = "============================================================\n";
    $entry .= "DATA/ORA : " . date('Y-m-d H:i:s') . "\n";
    $entry .= "IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'n/d') . "\n";
    $entry .= "METHOD : " . ($_SERVER['REQUEST_METHOD'] ?? 'n/d') . "\n";
    $entry .= "CONTENT : " . ($_SERVER['CONTENT_TYPE'] ?? 'n/d') . "\n";
    $entry .= "------------------------------------------------------------\n";
    $entry .= "RAW BODY :\n" . $raw . "\n";
    $entry .= "------------------------------------------------------------\n";
    $entry .= "DECODED_BLE :\n" . print_r($decoded, true);
    $entry .= "============================================================\n\n";

    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

function normalize_mac(string $mac): string
{
    // Rimuove separatori e uniforma in esadecimale maiuscolo
    return strtoupper(preg_replace('/[^0-9A-F]/i', '', $mac));
}

/**
 * Parse BLE Advertising data in AD structures:
 * formato: [len][type][value...]
 * Ritorna:
 *  - flags
 *  - structures (lista TLV)
 *  - manufacturer (company_id + data_hex)
 *  - ibeacon (se presente nel manufacturer)
 *  - errors
 */
function parse_ble_adv(array $bytes): array
{
    $res = [
        'flags'        => null,
        'structures'   => [],
        'manufacturer' => null,
        'ibeacon'      => null,
        'errors'       => [],
    ];

    $pos = 0;
    $n = count($bytes);

    while ($pos < $n) {
        $len = $bytes[$pos];
        $pos++;

        if ($len === 0) {
            break; // fine ADV
        }

        // len include (type + value). Quindi devono esserci almeno len byte rimanenti a partire da $pos
        if ($pos + $len > $n) {
            $res['errors'][] = 'truncated structure';
            break;
        }

        $type = $bytes[$pos];
        $pos++;

        $valueLen = $len - 1;
        $value = array_slice($bytes, $pos, $valueLen);
        $pos += $valueLen;

        $entry = [
            'type'      => sprintf('0x%02X', $type),
            'len'       => $valueLen,
            'value_hex' => bytes_to_hex($value),
        ];

        // Flags (0x01)
        if ($type === 0x01 && $valueLen >= 1) {
            $res['flags'] = sprintf('0x%02X', $value[0]);
        }

        // Manufacturer Specific Data (0xFF)
        if ($type === 0xFF && $valueLen >= 2) {
            // Company ID (Bluetooth) è little-endian nei primi 2 byte del value
            $cid = ($value[0]) | ($value[1] << 8);
            $mfg = array_slice($value, 2);

            $res['manufacturer'] = [
                'company_id' => sprintf('0x%04X', $cid),
                'data_hex'   => bytes_to_hex($mfg),
                'data_len'   => count($mfg),
            ];

            // iBeacon detection: 0x02 0x15 + 16B UUID + 2B major + 2B minor + 1B tx
            if (count($mfg) >= 23 && $mfg[0] === 0x02 && $mfg[1] === 0x15) {
                $uuidBytes = array_slice($mfg, 2, 16);
                $major = (($mfg[18] << 8) | $mfg[19]);
                $minor = (($mfg[20] << 8) | $mfg[21]);
                $tx = (int)to_signed8($mfg[22]);

                $res['ibeacon'] = [
                    'uuid'  => format_uuid($uuidBytes),
                    'major' => $major,
                    'minor' => $minor,
                    'tx'    => $tx,
                ];
            }

            // --- Decodifica custom GIRA (se riconosciuta) ---
            // Nel tuo caso: company_id 0xFFFF e payload 8 byte con marker 0x0B
            if ($res['manufacturer']['company_id'] === '0xFFFF' && $res['manufacturer']['data_len'] === 8) {
                $gira = decode_gira_mfg($res['manufacturer']['data_hex']);
                if ($gira !== null) {
                    $res['manufacturer']['gira'] = $gira;
                }
            }
        }

        $res['structures'][] = $entry;
    }

    return $res;
}

/**
 * Decodifica payload custom GIRA (8 bytes):
 * [0] marker (0x0B)
 * [1..2] x int16 BE signed
 * [3..4] y int16 BE signed
 * [5..6] z int16 BE signed
 * [7] stato uint8
 */
function decode_gira_mfg(string $dataHex): ?array
{
    $dataHex = strtoupper($dataHex);

    // 8 byte = 16 caratteri hex
    if (!preg_match('/^[0-9A-F]{16}$/', $dataHex)) {
        return null;
    }

    $b = hex_to_bytes($dataHex);
    if ($b === null || count($b) !== 8) {
        return null;
    }

    $marker = $b[0];

    // Se vuoi essere più stretto, sblocca questo check:
    // if ($marker !== 0x0B) return null;

    $x = int16be($b[1], $b[2]);
    $y = int16be($b[3], $b[4]);
    $z = int16be($b[5], $b[6]);
    $stato = $b[7];

    return [
        'marker' => sprintf('0x%02X', $marker),
        'x_hex'  => sprintf('%02X%02X', $b[1], $b[2]),
        'y_hex'  => sprintf('%02X%02X', $b[3], $b[4]),
        'z_hex'  => sprintf('%02X%02X', $b[5], $b[6]),
        'x'      => $x,
        'y'      => $y,
        'z'      => $z,
        'stato'  => $stato,
    ];
}

/* -------- helper minimi richiesti da parse_ble_adv -------- */

function hex_to_bytes(string $hex): ?array
{
    if ($hex === '' || (strlen($hex) % 2) !== 0) return null;
    if (!preg_match('/^[0-9A-F]+$/i', $hex)) return null;

    $hex = strtoupper($hex);
    $bytes = [];
    for ($i = 0; $i < strlen($hex); $i += 2) {
        $bytes[] = hexdec(substr($hex, $i, 2));
    }
    return $bytes;
}

function bytes_to_hex(array $bytes): string
{
    $s = '';
    foreach ($bytes as $b) $s .= sprintf('%02X', $b);
    return $s;
}

function to_signed8(int $byte): int
{
    return ($byte & 0x80) ? ($byte - 0x100) : $byte;
}

function int16be(int $hi, int $lo): int
{
    $v = (($hi & 0xFF) << 8) | ($lo & 0xFF);
    return ($v & 0x8000) ? ($v - 0x10000) : $v;
}

function format_uuid(array $b): string
{
    $hex = bytes_to_hex($b);
    return strtolower(
        substr($hex, 0, 8) . '-' .
            substr($hex, 8, 4) . '-' .
            substr($hex, 12, 4) . '-' .
            substr($hex, 16, 4) . '-' .
            substr($hex, 20, 12)
    );
}
