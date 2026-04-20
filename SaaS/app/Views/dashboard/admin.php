<?php include VIEW_PATH . 'layout/header.php'; ?>

<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <div class="page-header-sub"><?= htmlspecialchars($struttura['ragione_sociale']) ?></div>
  </div>
  <span id="gira-orologio" style="font-size:0.75rem; color:var(--muted);"></span>
</div>

<!-- Contatori struttura -->
<div class="stat-grid">
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/device'">
    <div class="stat-label">Device attivi</div>
    <div class="stat-value"><?= $tot_device ?></div>
    <div class="stat-sub">sensori in struttura</div>
  </div>
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/utenti'">
    <div class="stat-label">Utenti</div>
    <div class="stat-value"><?= $tot_utenti ?></div>
    <div class="stat-sub">operatori e medici</div>
  </div>
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/alert'">
    <div class="stat-label">Alert rossi</div>
    <div class="stat-value" id="gira-count-rossi" style="color:<?= ($alert_per_tipo['ROSSO'] ?? 0) > 0 ? 'var(--red)' : 'var(--green)' ?>">
      <?= $alert_per_tipo['ROSSO'] ?? 0 ?>
    </div>
    <div class="stat-sub">aperti ora</div>
  </div>
  <div class="stat-card clickable" onclick="location.href='<?= APP_URL ?>/alert'">
    <div class="stat-label">Alert arancio</div>
    <div class="stat-value" id="gira-count-arancio" style="color:<?= ($alert_per_tipo['ARANCIO'] ?? 0) > 0 ? 'var(--amber)' : 'var(--green)' ?>">
      <?= $alert_per_tipo['ARANCIO'] ?? 0 ?>
    </div>
    <div class="stat-sub">aperti ora</div>
  </div>
</div>

<!-- Stato device -->
<div class="card">
  <div class="flex-between mb-md">
    <p class="section-label" style="margin:0;">Stato device</p>
    <a href="<?= APP_URL ?>/device" class="btn btn--outline" style="font-size:0.7rem; padding:6px 14px;">Device →</a>
  </div>
  <div class="table-stack">
    <?php if (empty($device_stato)): ?>
      <div class="table-row">
        <span class="table-row__label text-muted">Nessun device registrato.</span>
      </div>
    <?php else: ?>
      <?php foreach ($device_stato as $d): ?>
        <?php
        $offline    = $d['ultimo_contatto'] === null || ($d['minuti_silenzio'] ?? 999) > 10;
        $bed_color  = 'var(--green)';
        $bed_label  = 'OK';
        if ($d['alert_tipo'] === 'ROSSO') {
          $bed_color = 'var(--red)';
          $bed_label = 'Rosso';
        } elseif ($d['alert_tipo'] === 'ARANCIO') {
          $bed_color = 'var(--amber)';
          $bed_label = 'Arancio';
        } elseif ($offline) {
          $bed_color = '#555';
          $bed_label = 'Offline';
        }
        ?>
        <div class="table-row" data-device-id="<?= $d['id'] ?>">
          <span class="gira-stato-pill" style="width:56px; flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:2px;">
            <?php $bed_size = 44;
            include VIEW_PATH . 'layout/_bed_icon.php'; ?>
            <span style="font-size:0.58rem; color:<?= $bed_color ?>; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">
              <?= $bed_label ?>
            </span>
          </span>
          <span class="table-row__label">
            <strong><?= htmlspecialchars($d['label'] ?? $d['mac']) ?></strong>
            <?php if ($d['area']): ?>
              <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
                <?= htmlspecialchars($d['area']) ?>
                <?= $d['subarea'] ? ' · ' . htmlspecialchars($d['subarea']) : '' ?>
              </span>
            <?php endif; ?>
          </span>
          <span class="table-row__meta" style="display:flex; gap:var(--space-md); align-items:center;">
            <?php if (!$offline && $d['posizione']): ?>
              <span class="gira-posizione" style="font-size:0.72rem;"><?= $d['posizione'] ?></span>
            <?php endif; ?>
            <?php if ($d['stato_batt'] !== null): ?>
              <span class="gira-batteria" style="font-size:0.72rem; color:<?= $d['stato_batt'] < 20 ? 'var(--amber)' : 'var(--muted)' ?>">
                🔋 <?= $d['stato_batt'] ?>%
              </span>
            <?php endif; ?>
          </span>
          <a href="<?= APP_URL ?>/device/show/<?= $d['id'] ?>"
            class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">
            →
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

<?php $extra_js = '<script src="' . APP_URL . '/assets/js/polling.js"></script>
<script>
const POLLING_INTERVAL = ' . POLLING_INTERVAL . ';
GiraPolling.avvia("dashboard-admin");
</script>'; ?>

<?php include VIEW_PATH . 'layout/footer.php'; ?>