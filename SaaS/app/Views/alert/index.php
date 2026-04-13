<?php
// ============================================================
//  GIRA · app/Views/alert/index.php
// ============================================================

// Conta per tipo
$per_tipo = [];
foreach ($alert as $a) {
    $per_tipo[$a['tipo']] = ($per_tipo[$a['tipo']] ?? 0) + 1;
}
?>

<div class="page-header">
  <div>
    <h1>Alert attivi</h1>
    <div class="page-header-sub"><?= count($alert) ?> alert apert<?= count($alert) === 1 ? 'o' : 'i' ?></div>
  </div>
  <a href="<?= APP_URL ?>/alert/storico" class="btn btn--outline">Storico →</a>
</div>

<!-- Contatori per tipo -->
<?php if (!empty($alert)): ?>
<div class="stat-grid" style="margin-bottom:var(--space-xl);">
  <?php
    $tipi = [
      'PULSANTE' => ['🆘 SOS',     'pill--red'],
      'ROSSO'    => ['🔴 Rosso',   'pill--red'],
      'ARANCIO'  => ['🟠 Arancio', 'pill--warn'],
      'BATTERIA' => ['🔋 Batteria','pill--warn'],
      'OFFLINE'  => ['📡 Offline', 'pill--muted'],
    ];
    foreach ($tipi as $tipo => [$label, $pill]):
      if (!isset($per_tipo[$tipo])) continue;
  ?>
  <div class="stat-card">
    <div class="stat-label"><?= $label ?></div>
    <div class="stat-value" style="font-size:1.8rem; color:<?= str_contains($pill, 'red') ? 'var(--red)' : (str_contains($pill, 'warn') ? 'var(--amber)' : 'var(--muted)') ?>">
      <?= $per_tipo[$tipo] ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Lista alert -->
<div class="table-stack">
  <?php if (empty($alert)): ?>
    <div class="table-row" style="padding:var(--space-xl); justify-content:center;">
      <span style="color:var(--green); font-size:0.9rem;">✓ Nessun alert aperto</span>
    </div>
  <?php else: ?>
    <?php foreach ($alert as $a): ?>
    <?php
      $pill_class = match($a['tipo']) {
        'ROSSO', 'PULSANTE' => 'pill--red',
        'ARANCIO', 'BATTERIA' => 'pill--warn',
        default => 'pill--muted',
      };
      $row_bg = match($a['tipo']) {
        'PULSANTE' => 'background:rgba(224,92,92,0.06);',
        'ROSSO'    => 'background:rgba(224,92,92,0.03);',
        default    => '',
      };
    ?>
    <div class="table-row" style="<?= $row_bg ?>">

      <!-- Tipo -->
      <span class="pill <?= $pill_class ?>" style="width:76px; flex-shrink:0;">
        <?= $a['tipo'] === 'PULSANTE' ? '🆘 SOS' : ucfirst(strtolower($a['tipo'])) ?>
      </span>

      <!-- Device + ubicazione -->
      <span class="table-row__label">
        <strong><?= htmlspecialchars($a['label'] ?? $a['mac']) ?></strong>
        <?php if ($a['area']): ?>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
            <?= htmlspecialchars($a['area'] . ($a['subarea'] ? ' · ' . $a['subarea'] : '')) ?>
          </span>
        <?php endif; ?>
        <?php if (Auth::isSuperadmin() || Auth::isMedico()): ?>
          <span style="color:var(--muted); font-size:0.7rem; margin-left:8px; display:inline-block;">
            🏥 <?= htmlspecialchars($a['struttura']) ?>
          </span>
        <?php endif; ?>
      </span>

      <!-- Durata -->
      <span class="table-row__meta" style="min-width:60px;">
        <?= $a['minuti_aperti'] ?> min
      </span>

      <!-- Aperto alle -->
      <span class="table-row__meta">
        <?= date('d/m H:i', strtotime($a['aperto_alle'])) ?>
      </span>

      <!-- Gestore -->
      <?php if ($a['gestito'] && $a['gestore_nome']): ?>
        <span class="pill pill--ok" style="font-size:0.65rem;">
          In carico: <?= htmlspecialchars($a['gestore_nome']) ?>
        </span>
      <?php else: ?>
        <span style="font-size:0.7rem; color:var(--muted);">Non gestito</span>
      <?php endif; ?>

      <!-- Azioni -->
      <div class="flex-center gap-sm">
        <?php if (!$a['gestito']): ?>
          <a href="<?= APP_URL ?>/alert/prendi-in-carico/<?= $a['id'] ?>"
             class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px; color:var(--amber); border-color:var(--amber);">
            Prendi in carico
          </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/alert/chiudi/<?= $a['id'] ?>"
           class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">
          Chiudi
        </a>
        <a href="<?= APP_URL ?>/device/show/<?= $a['id_device'] ?>"
           class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">
          Device →
        </a>
      </div>

    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
