<?php

declare(strict_types=1);

// ============================================================
// GIRA · ingest.php (LIGHT)
// - Riceve payload JSON dal gateway (HTTP POST, application/json)
// - Decodifica ADV BLE (Flags + Manufacturer Data)
// - Decodifica payload custom GIRA:
//     0B + X(2) + Y(2) + Z(2) + Stato(1)
//     dove X/Y/Z sono int16 signed BIG-ENDIAN
// - Scrive su gateway_log.txt SOLO:
//     timestamp,mac,x,y,z,stato
// ============================================================

// File log "light"
$log_file = __DIR__ . '/gateway_log.txt';

// Legge body grezzo
$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    respond_json(400, ['ok' => false, 'error' => 'empty body']);
}

// Decodifica JSON
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond_json(400, ['ok' => false, 'error' => 'invalid json']);
}

// Validazione minima (campi usati dal gateway)
$required = ['v', 'mid', 'time', 'ip', 'mac', 'devices', 'rssi'];
foreach ($required as $k) {
    if (!array_key_exists($k, $data)) {
        respond_json(400, ['ok' => false, 'error' => "missing $k"]);
    }
}
if (!is_array($data['devices'])) {
    respond_json(400, ['ok' => false, 'error' => 'devices must be array']);
}

// Decodifica devices
foreach ($data['devices'] as $dev) {
    // atteso: [0, "D1A3CD...", -64, "020106..."]
    if (!is_array($dev) || count($dev) < 4) {
        continue;
    }

    $deviceMac = normalize_mac((string)$dev[1]);
    $hex       = strtoupper(trim((string)$dev[3]));

    $bytes = hex_to_bytes($hex);
    if ($bytes === null) {
        continue;
    }

    $adv = parse_ble_adv($bytes);

    // Se c'è decodifica GIRA, logga SOLO i valori richiesti
    $g = $adv['manufacturer']['gira'] ?? null;
    if (is_array($g) && isset($g['x'], $g['y'], $g['z'], $g['stato'])) {
        log_light($log_file, $deviceMac, (int)$g['x'], (int)$g['y'], (int)$g['z'], (int)$g['stato']);
    }
}

// Risposta al gateway
respond_json(200, ['ok' => true]);

// ============================================================
// Helpers (HTTP + LOG)
// ============================================================

function respond_json(int $code, array $payload): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function log_light(string $log_file, string $mac, int $x, int $y, int $z, int $stato): void
{
    $ts = date('Y-m-d H:i:s');
    $line = $ts . ',' . $mac . ',' . $x . ',' . $y . ',' . $z . ',' . $stato . "\n";
    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

function normalize_mac(string $mac): string
{
    return strtoupper(preg_replace('/[^0-9A-F]/i', '', $mac));
}

// ============================================================
// Helpers (BLE decode)
// ============================================================

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

/**
 * Parse BLE Advertising data in AD structures:
 * formato: [len][type][value...]
 * Estrae:
 *  - flags (0x01)
 *  - manufacturer (0xFF)
 *  - ibeacon (se pattern 0x02 0x15 nel manufacturer payload)
 *  - gira (se company_id=0xFFFF e data_len=8 e marker=0x0B)
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

        if ($len === 0) break;

        if ($pos + $len > $n) {
            $res['errors'][] = 'truncated structure';
            break;
        }

        $type = $bytes[$pos];
        $pos++;

        $valueLen = $len - 1;
        $value = array_slice($bytes, $pos, $valueLen);
        $pos += $valueLen;

        // Flags
        if ($type === 0x01 && $valueLen >= 1) {
            $res['flags'] = sprintf('0x%02X', $value[0]);
        }

        // Manufacturer Specific Data
        if ($type === 0xFF && $valueLen >= 2) {
            $cid = ($value[0]) | ($value[1] << 8); // little-endian
            $mfg = array_slice($value, 2);

            $res['manufacturer'] = [
                'company_id' => sprintf('0x%04X', $cid),
                'data_hex'   => bytes_to_hex($mfg),
                'data_len'   => count($mfg),
            ];

            // iBeacon detection (se serve in futuro)
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

            // Decodifica custom GIRA
            if ($res['manufacturer']['company_id'] === '0xFFFF' && $res['manufacturer']['data_len'] === 8) {
                $gira = decode_gira_mfg($res['manufacturer']['data_hex']);
                if ($gira !== null) {
                    $res['manufacturer']['gira'] = $gira;
                }
            }
        }
    }

    return $res;
}

/**
 * Payload GIRA (8 bytes):
 * [0] marker (0x0B)
 * [1..2] x int16 BE signed
 * [3..4] y int16 BE signed
 * [5..6] z int16 BE signed
 * [7] stato uint8
 */
function decode_gira_mfg(string $dataHex): ?array
{
    $dataHex = strtoupper($dataHex);
    if (!preg_match('/^[0-9A-F]{16}$/', $dataHex)) return null;

    $b = hex_to_bytes($dataHex);
    if ($b === null || count($b) !== 8) return null;

    $marker = $b[0];
    if ($marker !== 0x0B) return null;

    $x = int16be($b[1], $b[2]);
    $y = int16be($b[3], $b[4]);
    $z = int16be($b[5], $b[6]);
    $stato = $b[7];

    return [
        'x' => $x,
        'y' => $y,
        'z' => $z,
        'stato' => $stato,
    ];
}
