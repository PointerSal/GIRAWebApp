<?php
// ============================================================
//  GIRA · app/Views/device/index.php
// ============================================================

// Filtro struttura attivo
$filtro_struttura = (int)($_GET['id_struttura'] ?? 0);
$device_filtrati  = $filtro_struttura
  ? array_filter($device, fn($d) => (int)$d['id_struttura'] === $filtro_struttura)
  : $device;

// Lista strutture uniche presenti nei device (per il dropdown filtro)
$strutture_uniche = [];
foreach ($device as $d) {
  $strutture_uniche[$d['id_struttura']] = $d['struttura_nome'];
}
asort($strutture_uniche);
?>

<div class="page-header">
  <div>
    <h1>Dispositivi</h1>
    <div class="page-header-sub"><?= count($device_filtrati) ?> sensori<?= $filtro_struttura ? ' · ' . htmlspecialchars($strutture_uniche[$filtro_struttura] ?? '') : '' ?></div>
  </div>
  <a href="<?= APP_URL ?>/device/crea<?= $filtro_struttura ? '?id_struttura=' . $filtro_struttura : '' ?>" class="btn btn--primary">+ Nuovo dispositivo</a>
</div>

<!-- Filtro struttura -->
<?php if (count($strutture_uniche) > 1): ?>
  <div class="flex-center gap-sm" style="margin-bottom:var(--space-lg);">
    <a href="<?= APP_URL ?>/device"
      class="btn btn--outline"
      <?= !$filtro_struttura ? 'style="color:var(--green); border-color:var(--green);"' : '' ?>>
      Tutte <span style="font-size:0.65rem; margin-left:4px; color:var(--muted);"><?= count($device) ?></span>
    </a>
    <?php foreach ($strutture_uniche as $sid => $snome): ?>
      <?php $tot = count(array_filter($device, fn($d) => (int)$d['id_struttura'] === (int)$sid)); ?>
      <a href="<?= APP_URL ?>/device?id_struttura=<?= $sid ?>"
        class="btn btn--outline"
        <?= $filtro_struttura === (int)$sid ? 'style="color:var(--green); border-color:var(--green);"' : '' ?>>
        <?= htmlspecialchars($snome) ?>
        <span style="font-size:0.65rem; margin-left:4px; color:var(--muted);"><?= $tot ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Lista device -->
<div class="table-stack">
  <?php if (empty($device_filtrati)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">Nessun device trovato.</span>
    </div>
  <?php else: ?>
    <?php foreach ($device_filtrati as $d): ?>
      <?php $offline = $d['ultimo_contatto'] === null || ($d['minuti_silenzio'] ?? 999) > 10; ?>
      <div class="table-row">

        <span class="status-dot <?= !$d['attivo'] ? 'status-dot--off' : ($offline ? 'status-dot--warn' : 'status-dot--ok') ?>"></span>

        <span class="table-row__label">
          <strong><?= htmlspecialchars($d['label'] ?? $d['mac']) ?></strong>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px; font-family:var(--font-mono);">
            <?= htmlspecialchars($d['mac']) ?>
          </span>
        </span>

        <span class="table-row__meta" style="min-width:140px;">
          🏥 <?= htmlspecialchars($d['struttura_nome']) ?>
        </span>

        <span class="table-row__meta" style="min-width:140px;">
          <?= $d['area'] ? htmlspecialchars($d['area'] . ($d['subarea'] ? ' · ' . $d['subarea'] : '')) : '<span style="color:var(--muted);">—</span>' ?>
        </span>

        <span class="table-row__meta" style="min-width:80px;">
          <?php if (!$d['attivo']): ?>
            <span style="color:var(--muted);">Disattivato</span>
          <?php elseif ($offline): ?>
            <span style="color:var(--amber);">Offline</span>
          <?php elseif ($d['posizione']): ?>
            <span style="color:var(--text);"><?= $d['posizione'] ?></span>
          <?php else: ?>
            <span style="color:var(--muted);">—</span>
          <?php endif; ?>
        </span>

        <?php if ($d['stato_batt'] !== null && $d['stato_batt'] > 0): ?>
          <span class="table-row__meta" style="color:<?= $d['stato_batt'] < 20 ? 'var(--amber)' : 'var(--muted)' ?>">
            🔋 <?= $d['stato_batt'] ?>%
          </span>
        <?php else: ?>
          <span class="table-row__meta" style="color:var(--muted);">🔋 —</span>
        <?php endif; ?>

        <div class="flex-center gap-sm">
          <a href="<?= APP_URL ?>/device/show/<?= $d['id'] ?>" class="btn btn--outline btn--icon" title="Dettaglio">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </a>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>