<?php
// ============================================================
//  GIRA · app/Views/alert/storico.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Storico alert</h1>
    <div class="page-header-sub"><?= $totale ?> alert chiusi</div>
  </div>
  <a href="<?= APP_URL ?>/alert" class="btn btn--outline">← Alert attivi</a>
</div>

<!-- Filtri -->
<div class="flex-center gap-sm" style="margin-bottom:var(--space-lg); flex-wrap:wrap;">

  <!-- Filtro tipo -->
  <?php
    $tipi_filtro = ['tutti' => 'Tutti', 'ROSSO' => '🔴 Rosso', 'ARANCIO' => '🟠 Arancio',
                    'BATTERIA' => '🔋 Batteria', 'OFFLINE' => '📡 Offline', 'PULSANTE' => '🆘 SOS'];
    foreach ($tipi_filtro as $key => $label):
      $attivo = $filtro_tipo === strtolower($key) || ($key === 'tutti' && $filtro_tipo === 'tutti');
      $url = APP_URL . '/alert/storico?tipo=' . strtolower($key) .
             ($filtro_struttura ? '&id_struttura=' . $filtro_struttura : '');
  ?>
    <a href="<?= $url ?>" class="btn btn--outline"
       <?= $attivo ? 'style="color:var(--green); border-color:var(--green);"' : '' ?>>
      <?= $label ?>
    </a>
  <?php endforeach; ?>

  <!-- Filtro struttura -->
  <?php if (!empty($strutture_map)): ?>
    <select onchange="location.href='<?= APP_URL ?>/alert/storico?tipo=<?= $filtro_tipo ?>&id_struttura='+this.value"
            style="background:var(--surface); border:1px solid var(--border); color:var(--text); font-family:var(--font-mono); font-size:0.75rem; padding:6px 12px; border-radius:var(--radius-sm);">
      <option value="0">Tutte le strutture</option>
      <?php foreach ($strutture_map as $sid => $snome): ?>
        <option value="<?= $sid ?>" <?= $filtro_struttura === (int)$sid ? 'selected' : '' ?>>
          <?= htmlspecialchars($snome) ?>
        </option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>

</div>

<!-- Lista storico -->
<div class="table-stack">
  <?php if (empty($alert)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">Nessun alert trovato.</span>
    </div>
  <?php else: ?>
    <?php foreach ($alert as $a): ?>
    <?php
      $pill_class = match($a['tipo']) {
        'ROSSO', 'PULSANTE' => 'pill--red',
        'ARANCIO', 'BATTERIA' => 'pill--warn',
        default => 'pill--muted',
      };
    ?>
    <div class="table-row">

      <span class="pill <?= $pill_class ?>" style="width:76px; flex-shrink:0;">
        <?= ucfirst(strtolower($a['tipo'])) ?>
      </span>

      <span class="table-row__label">
        <strong><?= htmlspecialchars($a['label'] ?? $a['mac']) ?></strong>
        <?php if ($a['area']): ?>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
            <?= htmlspecialchars($a['area'] . ($a['subarea'] ? ' · ' . $a['subarea'] : '')) ?>
          </span>
        <?php endif; ?>
        <span style="color:var(--muted); font-size:0.7rem; margin-left:8px;">
          🏥 <?= htmlspecialchars($a['struttura']) ?>
        </span>
      </span>

      <span class="table-row__meta">
        <?= date('d/m/Y H:i', strtotime($a['aperto_alle'])) ?>
      </span>

      <span class="table-row__meta">
        <?= $a['durata_totale'] ?? '—' ?> min
      </span>

      <?php if ($a['gestore_nome']): ?>
        <span class="table-row__meta" style="color:var(--green); font-size:0.72rem;">
          ✓ <?= htmlspecialchars($a['gestore_nome']) ?>
        </span>
      <?php else: ?>
        <span class="table-row__meta text-muted">—</span>
      <?php endif; ?>

      <?php if ($a['note']): ?>
        <span class="table-row__meta" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
              title="<?= htmlspecialchars($a['note']) ?>">
          📝 <?= htmlspecialchars($a['note']) ?>
        </span>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Paginazione -->
<?php if ($tot_pagine > 1): ?>
<div class="flex-center gap-sm" style="margin-top:var(--space-xl); justify-content:center;">
  <?php for ($p = 1; $p <= $tot_pagine; $p++): ?>
    <?php
      $url_p = APP_URL . '/alert/storico?tipo=' . $filtro_tipo .
               ($filtro_struttura ? '&id_struttura=' . $filtro_struttura : '') .
               '&p=' . $p;
    ?>
    <a href="<?= $url_p ?>" class="btn btn--outline"
       style="<?= $p === $pagina ? 'color:var(--green); border-color:var(--green);' : '' ?> padding:4px 12px; font-size:0.75rem;">
      <?= $p ?>
    </a>
  <?php endfor; ?>
</div>
<?php endif; ?>
