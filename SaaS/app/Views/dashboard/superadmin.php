<?php include VIEW_PATH . 'layout/header.php'; ?>

<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <div class="page-header-sub">Visione globale piattaforma</div>
  </div>
  <span id="gira-orologio" style="font-size:0.75rem; color:var(--muted);"></span>
</div>

<!-- Statistiche globali -->
<div class="stat-grid">
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/strutture'">
    <div class="stat-label">Strutture attive</div>
    <div class="stat-value"><?= $tot_strutture ?></div>
    <div class="stat-sub">RSA sulla piattaforma</div>
  </div>
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/utenti'">
    <div class="stat-label">Utenti totali</div>
    <div class="stat-value"><?= $tot_utenti ?></div>
    <div class="stat-sub">su tutte le strutture</div>
  </div>
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/device'">
    <div class="stat-label">Device attivi</div>
    <div class="stat-value"><?= $tot_device ?></div>
    <div class="stat-sub">sensori connessi</div>
  </div>
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/alert'">
    <div class="stat-label">Alert aperti</div>
    <div class="stat-value" style="color:<?= $tot_alert > 0 ? 'var(--red)' : 'var(--green)' ?>">
      <?= $tot_alert ?>
    </div>
    <div class="stat-sub">su tutta la piattaforma</div>
  </div>
</div>

<!-- Alert rossi urgenti -->
<?php if (!empty($alert_rossi)): ?>
  <div class="card card--accent-left card--red" style="margin-bottom:var(--space-xl);">
    <div class="flex-between mb-md">
      <p class="section-label" style="margin:0;">🔴 Alert rossi aperti</p>
      <a href="<?= APP_URL ?>/alert" class="btn btn--outline" style="font-size:0.7rem; padding:4px 12px;">Vedi tutti →</a>
    </div>
    <div class="table-stack">
      <?php foreach ($alert_rossi as $a): ?>
        <div class="table-row">
          <span class="pill pill--red" style="width:64px;">Rosso</span>
          <span class="table-row__label">
            <?= htmlspecialchars($a['struttura']) ?> —
            <?= htmlspecialchars($a['label'] ?? $a['mac']) ?>
          </span>
          <span class="table-row__meta"><?= $a['minuti_aperti'] ?> min</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Strutture -->
<div class="card">
  <div class="flex-between mb-md">
    <p class="section-label" style="margin:0;">Strutture RSA</p>
    <a href="<?= APP_URL ?>/strutture/crea" class="btn btn--primary" style="font-size:0.7rem; padding:6px 14px;">+ Nuova</a>
  </div>
  <div class="table-stack">
    <?php if (empty($strutture)): ?>
      <div class="table-row"><span class="table-row__label text-muted">Nessuna struttura registrata.</span></div>
    <?php else: ?>
      <?php foreach ($strutture as $s): ?>
        <div class="table-row">
          <span class="table-row__label">
            <strong><?= htmlspecialchars($s['ragione_sociale']) ?></strong>
          </span>
          <span class="table-row__meta" style="display:flex; gap:var(--space-md);">
            <span>📡 <?= $s['tot_device'] ?></span>
            <span>👥 <?= $s['tot_utenti'] ?></span>
            <?php if ($s['alert_aperti'] > 0): ?>
              <span style="color:var(--red);">🔔 <?= $s['alert_aperti'] ?></span>
            <?php endif; ?>
          </span>
          <a href="<?= APP_URL ?>/strutture/show/<?= $s['id'] ?>"
            class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">
            Dettaglio →
          </a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  function aggiornaOrologio() {
    const now = new Date();
    const d = String(now.getDate()).padStart(2, '0');
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const Y = now.getFullYear();
    const H = String(now.getHours()).padStart(2, '0');
    const i = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('gira-orologio').textContent = d + '/' + m + '/' + Y + ' ' + H + ':' + i;
  }
  aggiornaOrologio();
  setInterval(aggiornaOrologio, 1000);
</script>

<?php include VIEW_PATH . 'layout/footer.php'; ?>