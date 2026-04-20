<?php
// ============================================================
//  GIRA · app/Views/strutture/index.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Strutture RSA</h1>
    <div class="page-header-sub"><?= $contatori['tutte'] ?> struttur<?= $contatori['tutte'] === 1 ? 'a' : 'e' ?> registrat<?= $contatori['tutte'] === 1 ? 'a' : 'e' ?></div>
  </div>
  <a href="<?= APP_URL ?>/strutture/crea" class="btn btn--primary">+ Nuova struttura</a>
</div>

<!-- Tab filtro -->
<div class="flex-center gap-sm" style="margin-bottom:var(--space-lg);">
  <?php
  $tabs = ['tutte' => 'Tutte', 'attiva' => 'Attive', 'sospesa' => 'Sospese'];
  foreach ($tabs as $key => $label):
    $attivo = $filtro === $key ? 'style="color:var(--green); border-color:var(--green);"' : '';
  ?>
    <a href="<?= APP_URL ?>/strutture?stato=<?= $key ?>"
      class="btn btn--outline" <?= $attivo ?>>
      <?= $label ?>
      <span style="font-size:0.65rem; margin-left:4px; color:var(--muted);">
        <?= $contatori[$key] ?? 0 ?>
      </span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Lista strutture -->
<div class="table-stack">
  <?php if (empty($strutture)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">Nessuna struttura trovata.</span>
    </div>
  <?php else: ?>
    <?php foreach ($strutture as $s): ?>
      <div class="table-row">

        <!-- Stato -->
        <span class="status-dot <?= $s['attiva'] ? 'status-dot--ok' : 'status-dot--off' ?>"></span>

        <!-- Nome + P.IVA -->
        <span class="table-row__label">
          <strong><?= htmlspecialchars($s['ragione_sociale']) ?></strong>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
            <?= htmlspecialchars($s['partita_iva']) ?>
          </span>
        </span>

        <!-- Contatori -->
        <span class="table-row__meta" style="display:flex; gap:var(--space-md);">
          <span title="Device">📡 <?= $s['tot_device'] ?></span>
          <span title="Utenti">👥 <?= $s['tot_utenti'] ?></span>
          <?php if ($s['alert_aperti'] > 0): ?>
            <span style="color:var(--red);" title="Alert aperti">🔔 <?= $s['alert_aperti'] ?></span>
          <?php endif; ?>
        </span>

        <!-- Piano -->
        <?php if ($s['piano']): ?>
          <span class="pill pill--<?= $s['sub_stato'] === 'ATTIVA' ? 'ok' : 'muted' ?>">
            <?= $s['piano'] ?>
          </span>
        <?php endif; ?>

        <!-- Azioni -->
        <div class="flex-center gap-sm">
          <a href="<?= APP_URL ?>/strutture/show/<?= $s['id'] ?>" class="btn btn--outline btn--icon" title="Dettaglio">
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