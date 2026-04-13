<?php

declare(strict_types=1);

// ============================================================
// GIRA · vedo.php (LIGHT-LOG READER)
// Legge gateway_log.txt in formato riga CSV:
//   timestamp,mac,x,y,z,stato
// e mostra una tabella semplice.
// ============================================================

ob_start();

$log_file = __DIR__ . '/gateway_log.txt';

// Svuota log
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    file_put_contents($log_file, '');
    header('Location: vedo.php');
    exit;
}

// Quante righe mostrare (default 50)
$n = isset($_GET['n']) ? max(1, min(1000, (int)$_GET['n'])) : 50;

// Legge file
$lines = [];
if (file_exists($log_file)) {
    $raw = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($raw)) {
        $lines = $raw;
    }
}

// Parsing CSV per riga
$rows = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    // opzionale: salta header CSV
    if (stripos($line, 'timestamp,mac,') === 0) continue;

    // split CSV semplice (6 campi attesi)
    $parts = str_getcsv($line);
    if (count($parts) < 6) continue;

    [$ts, $mac, $x, $y, $z, $stato] = array_slice($parts, 0, 6);

    $rows[] = [
        'ts'    => $ts,
        'mac'   => strtoupper(preg_replace('/[^0-9A-F]/i', '', (string)$mac)),
        'x'     => is_numeric($x) ? (int)$x : null,
        'y'     => is_numeric($y) ? (int)$y : null,
        'z'     => is_numeric($z) ? (int)$z : null,
        'stato' => is_numeric($stato) ? (int)$stato : null,
    ];
}

// Mostra solo le ultime N righe
$total = count($rows);
if ($total > $n) {
    $rows = array_slice($rows, $total - $n, $n);
}

// Helper HTML
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Timestamp ultimo record
$latestTs = $total > 0 ? $rows[count($rows) - 1]['ts'] : 'n/d';

?>
<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GIRA · Vedo (Light)</title>
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

        .right {
            margin-left: auto;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .small {
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top">
            <div>
                <strong>GIRA · Vedo (Light)</strong>
                <span class="pill">Auto-refresh 5s</span>
                <span class="pill">Mostro ultime <?php echo (int)$n; ?> righe</span>
            </div>
            <div class="right">
                <a href="vedo.php">↻ Aggiorna</a>
                <a href="vedo.php?clear=1">✕ Svuota log</a>
            </div>
        </div>

        <div class="card">
            <div><span class="muted">Righe totali:</span> <span class="ok"><?php echo (int)$total; ?></span></div>
            <div><span class="muted">Ultimo timestamp:</span> <span class="ok"><?php echo h($latestTs); ?></span></div>
            <div class="muted small">Formato atteso: <code>timestamp,mac,x,y,z,stato</code></div>
        </div>

        <div class="card">
            <?php if (empty($rows)): ?>
                <div class="muted">Nessuna riga valida trovata in gateway_log.txt.</div>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo h((string)$r['ts']); ?></td>
                                <td><?php echo h((string)$r['mac']); ?></td>
                                <td><?php echo h($r['x'] === null ? '' : (string)$r['x']); ?></td>
                                <td><?php echo h($r['y'] === null ? '' : (string)$r['y']); ?></td>
                                <td><?php echo h($r['z'] === null ? '' : (string)$r['z']); ?></td>
                                <td><?php echo h($r['stato'] === null ? '' : (string)$r['stato']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card small muted">
            Suggerimento: cambia numero righe con <code>?n=200</code> (max 1000).
        </div>
    </div>
</body>

</html>