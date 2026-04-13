<?php

declare(strict_types=1);

// ============================================================
// GIRA · Gateway Log Viewer (viewer.php)
// Legge gateway_log.txt e mostra pacchetti (RAW, JSON formattato, DECODED_BLE)
// ============================================================

ob_start(); // evita "headers already sent" in caso di output accidentale

$log_file = __DIR__ . '/gateway_log.txt';

// --- Clear log ---
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
  file_put_contents($log_file, '');
  header('Location: viewer.php');
  exit;
}

// --- Read log ---
$log = file_exists($log_file) ? file_get_contents($log_file) : '';
$log = $log === false ? '' : $log;

// I pacchetti sono separati dalla riga di = (come scrivi in ingest/receiver)
$chunks = preg_split("/=+\n/", $log);
$chunks = array_values(array_filter(array_map('trim', $chunks)));

function h(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function extract_section(string $chunk, string $label): string
{
  // prende tutto dopo "$label :" fino alla prossima linea di trattini o fine chunk
  $pattern = '/' . preg_quote($label, '/') . '\s*:\s*\n(.*?)(\n-+\n|\z)/s';
  if (preg_match($pattern, $chunk, $m)) {
    return trim($m[1]);
  }
  return '';
}

function extract_header_value(string $chunk, string $key): string
{
  $pattern = '/^' . preg_quote($key, '/') . '\s*:\s*(.*)$/m';
  if (preg_match($pattern, $chunk, $m)) {
    return trim($m[1]);
  }
  return '';
}

function prettify_json(string $raw): string
{
  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) return '';
  return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

?>
<!doctype html>
<html lang="it">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>GIRA · Gateway Log Viewer</title>
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

    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media (max-width: 900px) {
      .grid {
        grid-template-columns: 1fr;
      }
    }

    pre {
      white-space: pre-wrap;
      word-break: break-word;
      margin: 0;
      padding: 10px;
      background: #0f1412;
      border: 1px solid #1e2a26;
      border-radius: 6px;
      color: #d4e8df;
    }

    .muted {
      color: #5a7a6e;
    }

    .title {
      font-size: 16px;
      font-weight: 700;
    }

    .k {
      color: #f5a623;
    }

    .ok {
      color: #3ddc84;
    }

    .err {
      color: #e05c5c;
    }

    .sep {
      height: 1px;
      background: #1e2a26;
      margin: 10px 0;
    }

    details summary {
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="wrap">

    <div class="top">
      <div class="title">GIRA · Gateway Log Viewer</div>
      <div>
        <span class="pill">Auto-refresh 5s</span>
        <a href="viewer.php">↻ Aggiorna</a>
        &nbsp;|&nbsp;
        <a href="viewer.php?clear=1" onclick="return confirm('Svuotare gateway_log.txt?')">✕ Svuota log</a>
      </div>
    </div>

    <?php if (empty($chunks)): ?>
      <div class="card">
        <div class="muted">Nessun pacchetto ricevuto ancora.</div>
        <div class="muted">In attesa di POST su ingest.php</div>
      </div>
    <?php else: ?>
      <div class="card">
        <div><span class="k">Pacchetti:</span> <span class="ok"><?php echo count($chunks); ?></span></div>
      </div>

      <?php
      // Mostra dal più recente al più vecchio
      $chunks = array_reverse($chunks);

      foreach ($chunks as $idx => $chunk):
        $when    = extract_header_value($chunk, 'DATA/ORA');
        $ip      = extract_header_value($chunk, 'IP');
        $method  = extract_header_value($chunk, 'METHOD');
        $content = extract_header_value($chunk, 'CONTENT');

        $rawBody = extract_section($chunk, 'RAW BODY');
        // ingest.php scrive "DECODED_BLE", receiver.php "DECODED"
        $decodedBle = extract_section($chunk, 'DECODED_BLE');
        $decodedOld = $decodedBle === '' ? extract_section($chunk, 'DECODED') : '';

        $prettyJson = prettify_json($rawBody);
      ?>
        <div class="card">
          <div class="grid">
            <div>
              <div><span class="k">#</span> <?php echo $idx + 1; ?></div>
              <div><span class="k">DATA/ORA</span>: <?php echo h($when ?: 'n/d'); ?></div>
              <div><span class="k">IP</span>: <?php echo h($ip ?: 'n/d'); ?></div>
            </div>
            <div>
              <div><span class="k">METHOD</span>: <?php echo h($method ?: 'n/d'); ?></div>
              <div><span class="k">CONTENT</span>: <?php echo h($content ?: 'n/d'); ?></div>
            </div>
          </div>

          <div class="sep"></div>

          <details>
            <summary><span class="k">Body grezzo</span> <span class="muted">(RAW BODY)</span></summary>
            <pre><?php echo h($rawBody ?: '(vuoto)'); ?></pre>
          </details>

          <div class="sep"></div>

          <details <?php echo $prettyJson ? 'open' : ''; ?>>
            <summary><span class="k">JSON formattato</span></summary>
            <pre><?php echo h($prettyJson ?: '(non è JSON valido)'); ?></pre>
          </details>

          <div class="sep"></div>

          <details open>
            <summary>
              <span class="k">Decoded</span>
              <span class="muted"><?php echo $decodedBle !== '' ? '(DECODED_BLE)' : '(DECODED)'; ?></span>
            </summary>

            <?php if ($decodedBle !== ''): ?>
              <pre><?php echo h($decodedBle); ?></pre>
            <?php elseif ($decodedOld !== ''): ?>
              <pre><?php echo h($decodedOld); ?></pre>
            <?php else: ?>
              <pre>(nessuna sezione decoded trovata)</pre>
            <?php endif; ?>
          </details>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</body>

</html>
<?php ob_end_flush(); ?>