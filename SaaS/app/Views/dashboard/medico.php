<?php include VIEW_PATH . 'layout/header.php'; ?>

<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <div class="page-header-sub">Visione clinica · <?= count($strutture_ids) ?> struttura<?= count($strutture_ids) > 1 ? 'e' : '' ?></div>
  </div>
  <span style="font-size:0.75rem; color:var(--muted);"><?= date('d/m/Y H:i') ?></span>
</div>

<!-- Alert aperti -->
<div class="card" style="margin-bottom:var(--space-xl);">
  <div class="flex-between mb-md">
    <p class="section-label" style="margin:0;">Alert aperti</p>
    <a href="<?= APP_URL ?>/alert" class="btn btn--outline" style="font-size:0.7rem; padding:4px 12px;">Vedi tutti →</a>
  </div>

  <?php if (empty($alert_aperti)): ?>
    <div class="table-row">
      <span class="status-dot status-dot--ok"></span>
      <span class="table-row__label" style="color:var(--green);">Nessun alert aperto</span>
    </div>
  <?php else: ?>
    <div class="table-stack">
      <?php foreach ($alert_aperti as $a): ?>
      <?php
        $pill_class = 'pill--warn';
        if ($a['tipo'] === 'ROSSO' || $a['tipo'] === 'PULSANTE') $pill_class = 'pill--red';
        if ($a['tipo'] === 'BATTERIA' || $a['tipo'] === 'OFFLINE') $pill_class = 'pill--muted';
      ?>
      <div class="table-row">
        <span class="pill <?= $pill_class ?>" style="width:72px; flex-shrink:0;">
          <?= $a['tipo'] === 'PULSANTE' ? '🆘 SOS' : ucfirst(strtolower($a['tipo'])) ?>
        </span>
        <span class="table-row__label">
          <strong><?= htmlspecialchars($a['label'] ?? $a['mac']) ?></strong>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
            <?= htmlspecialchars($a['struttura']) ?>
            <?php if ($a['area']): ?>
              · <?= htmlspecialchars($a['area']) ?>
              <?= $a['subarea'] ? ' · ' . htmlspecialchars($a['subarea']) : '' ?>
            <?php endif; ?>
          </span>
        </span>
        <span class="table-row__meta"><?= $a['minuti_aperti'] ?> min</span>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Storico posizioni recente -->
<div class="card">
  <p class="section-label">Storico posizioni · ultime 4 ore</p>
  <div class="table-stack">
    <?php if (empty($storico)): ?>
      <div class="table-row">
        <span class="table-row__label text-muted">Nessun dato disponibile.</span>
      </div>
    <?php else: ?>
      <?php foreach ($storico as $s): ?>
      <div class="table-row">
        <span class="table-row__label">
          <strong><?= htmlspecialchars($s['label'] ?? $s['id_device']) ?></strong>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
            <?= htmlspecialchars($s['struttura']) ?>
          </span>
        </span>
        <span style="font-size:0.75rem; color:var(--text);"><?= $s['posizione'] ?></span>
        <?php if ($s['durata_minuti']): ?>
          <span class="table-row__meta"><?= $s['durata_minuti'] ?> min</span>
        <?php endif; ?>
        <span class="table-row__meta"><?= date('H:i', strtotime($s['iniziato_alle'])) ?></span>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php include VIEW_PATH . 'layout/footer.php'; ?>
