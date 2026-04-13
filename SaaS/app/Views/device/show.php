<?php
// ============================================================
//  GIRA · app/Views/device/show.php
// ============================================================
$offline = isset($stato) && $stato
    ? (abs(strtotime($stato['ultimo_contatto']) - time()) > 600)
    : true;
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($device['label'] ?? $device['mac']) ?></h1>
    <div class="page-header-sub" style="font-family:var(--font-mono);">
      <?= htmlspecialchars($device['mac']) ?>
    </div>
  </div>
  <div class="flex-center gap-sm">
    <a href="<?= APP_URL ?>/device/modifica/<?= $device['id'] ?>"
       class="btn btn--outline">Modifica</a>
    <a href="<?= APP_URL ?>/device/assegna/<?= $device['id'] ?>"
       class="btn btn--outline">Ubicazione</a>
    <a href="<?= APP_URL ?>/device"
       class="btn btn--outline">← Device</a>
  </div>
</div>

<!-- Stato real-time -->
<div class="grid grid--2" style="margin-bottom:var(--space-xl);">

  <div class="card">
    <p class="section-label">Stato attuale</p>
    <?php if ($stato): ?>
    <table style="width:100%; font-size:0.82rem; border-collapse:collapse;">
      <?php
        $pill_pos = match($stato['posizione']) {
          'SUPINO' => 'pill--ok',
          'LATO_A', 'LATO_B' => 'pill--warn',
          'PRONO'  => 'pill--muted',
          default  => 'pill--muted',
        };
        $campi = [
          'Connessione' => $offline
            ? '<span style="color:var(--amber);">⚠ Offline</span>'
            : '<span style="color:var(--green);">● Online</span>',
          'Posizione'   => '<span class="pill ' . $pill_pos . '">' . $stato['posizione'] . '</span>',
          'Batteria'    => $stato['stato_batt'] !== null
            ? '<span style="color:' . ($stato['stato_batt'] < 20 ? 'var(--amber)' : 'var(--text)') . '">🔋 ' . $stato['stato_batt'] . '%</span>'
            : '—',
          'Segnale'     => $stato['stato_segnale'] !== null ? $stato['stato_segnale'] . ' dBm' : '—',
          'Pulsante'    => $stato['stato_pulsante'] ? '<span style="color:var(--red);">🆘 Premuto</span>' : 'Non premuto',
          'Ultimo dato' => date('d/m/Y H:i:s', strtotime($stato['ultimo_contatto'])),
        ];
        foreach ($campi as $label => $valore):
      ?>
        <tr>
          <td style="color:var(--muted); padding:6px 0; width:110px;"><?= $label ?></td>
          <td><?= $valore ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <p style="color:var(--muted); font-size:0.82rem;">Nessun dato ricevuto ancora.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <p class="section-label">Configurazione</p>
    <table style="width:100%; font-size:0.82rem; border-collapse:collapse;">
      <?php
        $campi_cfg = [
          'Stato'      => $device['attivo']
            ? '<span class="pill pill--ok">Attivo</span>'
            : '<span class="pill pill--muted">Disattivato</span>',
          'Struttura'  => htmlspecialchars(
              Database::getInstance()->prepare('SELECT ragione_sociale FROM gir_struttura WHERE id = :id LIMIT 1')
                ->execute([':id' => $device['id_struttura']]) ? '' : ''
          ),
          'Ubicazione' => $ubicazione
            ? htmlspecialchars($ubicazione['area'] . ($ubicazione['subarea'] ? ' · ' . $ubicazione['subarea'] : ''))
            : '<span style="color:var(--muted);">Non assegnata</span>',
          'Registrato' => date('d/m/Y', strtotime($device['creato_il'])),
        ];

        // Struttura nome
        $stmt_s = Database::getInstance()->prepare('SELECT ragione_sociale FROM gir_struttura WHERE id = :id LIMIT 1');
        $stmt_s->execute([':id' => $device['id_struttura']]);
        $campi_cfg['Struttura'] = htmlspecialchars($stmt_s->fetchColumn() ?: '—');

        foreach ($campi_cfg as $label => $valore):
      ?>
        <tr>
          <td style="color:var(--muted); padding:6px 0; width:110px;"><?= $label ?></td>
          <td><?= $valore ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

</div>

<!-- Alert aperti -->
<?php if (!empty($alert)): ?>
<div class="card" style="margin-bottom:var(--space-xl);">
  <p class="section-label">Alert aperti</p>
  <div class="table-stack">
    <?php foreach ($alert as $a): ?>
    <?php
      $pill = match($a['tipo']) {
        'ROSSO','PULSANTE' => 'pill--red',
        'ARANCIO','BATTERIA' => 'pill--warn',
        default => 'pill--muted',
      };
    ?>
    <div class="table-row">
      <span class="pill <?= $pill ?>" style="width:72px; flex-shrink:0;"><?= $a['tipo'] ?></span>
      <span class="table-row__label">
        Aperto il <?= date('d/m/Y H:i', strtotime($a['aperto_alle'])) ?>
      </span>
      <span class="table-row__meta">
        <?= (int)((time() - strtotime($a['aperto_alle'])) / 60) ?> min fa
      </span>
      <a href="<?= APP_URL ?>/alert/prendi-in-carico/<?= $a['id'] ?>"
         class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">Gestisci</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Storico posizioni -->
<div class="card">
  <p class="section-label">Storico posizioni · ultimi 7 giorni</p>
  <div class="table-stack">
    <?php if (empty($storico)): ?>
      <div class="table-row">
        <span class="table-row__label text-muted">Nessun dato disponibile.</span>
      </div>
    <?php else: ?>
      <?php foreach ($storico as $s): ?>
      <?php
        $pill_s = match($s['posizione']) {
          'SUPINO' => 'pill--ok',
          'LATO_A','LATO_B' => 'pill--warn',
          'PRONO'  => 'pill--muted',
          default  => 'pill--muted',
        };
      ?>
      <div class="table-row">
        <span class="pill <?= $pill_s ?>" style="width:80px; flex-shrink:0;">
          <?= $s['posizione'] ?>
        </span>
        <span class="table-row__label">
          <?= date('d/m H:i', strtotime($s['iniziato_alle'])) ?>
          <?php if ($s['terminato_alle']): ?>
            → <?= date('H:i', strtotime($s['terminato_alle'])) ?>
          <?php else: ?>
            <span style="color:var(--green); font-size:0.7rem;">● in corso</span>
          <?php endif; ?>
        </span>
        <span class="table-row__meta">
          <?php if ($s['durata_minuti']): ?>
            <?= $s['durata_minuti'] ?> min
          <?php elseif (!$s['terminato_alle']): ?>
            <?= (int)((time() - strtotime($s['iniziato_alle'])) / 60) ?> min
          <?php endif; ?>
        </span>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
