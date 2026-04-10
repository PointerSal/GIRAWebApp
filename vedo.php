<?php

declare(strict_types=1);

// ============================================================
// GIRA · vedo.php
// Mostra solo: timestamp, mac, x, y, z, stato (da gateway_log.txt)
// ============================================================

ob_start();

$log_file = __DIR__ . '/gateway_log.txt';

// Svuota log
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    file_put_contents($log_file, '');
    header('Location: vedo.php');
    exit;
}

// Legge log
$log = file_exists($log_file) ? file_get_contents($log_file) : '';
$log = ($log === false) ? '' : $log;

// Split pacchetti: nel tuo log ogni entry è racchiusa tra righe di "====="
$chunks = preg_split("/=+\n/", $log);
$chunks = array_values(array_filter(array_map('trim', $chunks)));

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function extract_header_value(string $chunk, string $key): string
{
    if (preg_match('/^' . preg_quote($key, '/') . '\s*:\s*(.*)$/m', $chunk, $m)) {
        return trim($m[1]);
    }
    return '';
}

function extract_section(string $chunk, string $label): string
{
    // Prende tutto dopo "$label :" fino alla prossima riga di trattini oppure fine
    $pattern = '/' . preg_quote($label, '/') . '\s*:\s*\n(.*?)(\n-+\n|\z)/s';
    if (preg_match($pattern, $chunk, $m)) {
        return trim($m[1]);
    }
    return '';
}

// Estrae righe: [mac] => ... [x] => ... [y] => ... [z] => ... [stato] => ...
function parse_decoded_ble_rows(string $decoded): array
{
    $rows = [];

    // Cerchiamo blocchi "device" che contengono mac e gira
    // Approccio robusto: estrai tutte le occorrenze del pattern mac + x/y/z/stato
    $pattern = '/\[\s*mac\s*\]\s*=>\s*([0-9A-F]+).*?\[\s*gira\s*\]\s*=>\s*Array\s*\(\s*(.*?)\)\s*/si';

    if (preg_match_all($pattern, $decoded, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $mac = strtoupper(trim($m[1]));
            $giraBlock = $m[2];

            $x = extract_num($giraBlock, 'x');
            $y = extract_num($giraBlock, 'y');
            $z = extract_num($giraBlock, 'z');
            $stato = extract_num($giraBlock, 'stato');

            // rssi del device (se presente nello stesso "device block")
            $rssi = null;
            if (preg_match('/\[\s*rssi\s*\]\s*=>\s*(-?\d+)/i', $m[0], $rm)) {
                $rssi = (int)$rm[1];
            }

            // Se gira non c'è davvero (x/y/z/stato null), salta
            if ($x === null && $y === null && $z === null && $stato === null) {
                continue;
            }

            $rows[] = [
                'mac' => $mac,
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'stato' => $stato,
                'rssi' => $rssi,
            ];
        }
    }

    return $rows;
}

function extract_num(string $block, string $key): ?int
{
    if (preg_match('/\[\s*' . preg_quote($key, '/') . '\s*\]\s*=>\s*(-?\d+)/i', $block, $m)) {
        return (int)$m[1];
    }
    return null;
}

// Costruisce righe tabella prendendo l’ultima entry (o tutte, se vuoi)
$rows = [];
$latestWhen = null;

if (!empty($chunks)) {
    // Prendi l’ultima entry (più recente): nel log append, l’ultima è in fondo
    $lastChunk = $chunks[count($chunks) - 1];

    $latestWhen = extract_header_value($lastChunk, 'DATA/ORA');
    $decodedBle = extract_section($lastChunk, 'DECODED_BLE');
    if ($decodedBle === '') {
        // fallback se nel log c'è ancora "DECODED" vecchio stile
        $decodedBle = extract_section($lastChunk, 'DECODED');
    }

    if ($decodedBle !== '') {
        $rows = parse_decoded_ble_rows($decodedBle);
    }
}

?>
<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GIRA · Viewer Light</title>
    <meta http-equiv="refresh" content="5">
    <style>
        body {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            background: #0b0f0e;
            color: #d4e8df;
            margin: 0;
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 16px;
        }

        a {
            color: #3ddc84;
            text-decoration: none;
        }

        .top {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 12px 0;
        }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #1e2a26;
            border-radius: 4px;
            color: #5a7a6e;
            font-size: 12px;
        }

        .card {
            border: 1px solid #1e2a26;
            border-radius: 6px;
            background: #111715;
            padding: 12px;
            margin: 12px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid #1e2a26;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        th {
            color: #f5a623;
            font-weight: 600;
        }

        .muted {
            color: #5a7a6e;
        }

        .ok {
            color: #3ddc84;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top">
            <div>
                <strong>GIRA · Viewer Light</strong>
                <span class="pill">Auto-refresh 5s</span>
            </div>
            <div>
                <a href="vedo.php">↻ Aggiorna</a>
                &nbsp;|&nbsp;
                <a href="vedo.php?clear=1">✕ Svuota log</a>
            </div>
        </div>

        <div class="card">
            <div><span class="muted">Ultimo pacchetto:</span> <span class="ok"><?php echo h($latestWhen ?: 'n/d'); ?></span></div>
            <div class="muted">Mostro solo i device che hanno `manufacturer.gira` (x,y,z,stato).</div>
        </div>

        <div class="card">
            <?php if (empty($rows)): ?>
                <div class="muted">Nessun dato x/y/z/stato trovato nell’ultima entry (controlla che `ingest.php` scriva `DECODED_BLE`).</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>MAC</th>
                            <th>X</th>
                            <th>Y</th>
                            <th>Z</th>
                            <th>Stato</th>
                            <th>RSSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo h($latestWhen ?: 'n/d'); ?></td>
                                <td><?php echo h($r['mac']); ?></td>
                                <td><?php echo h((string)($r['x'] ?? '')); ?></td>
                                <td><?php echo h((string)($r['y'] ?? '')); ?></td>
                                <td><?php echo h((string)($r['z'] ?? '')); ?></td>
                                <td><?php echo h((string)($r['stato'] ?? '')); ?></td>
                                <td><?php echo h((string)($r['rssi'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php ob_end_flush(); ?>