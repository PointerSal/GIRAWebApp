<?php include VIEW_PATH . 'layout/header.php'; ?>

<div class="page-header">
  <div>
    <h1>Bentornato, <?= htmlspecialchars($utente['nome']) ?></h1>
    <div class="page-header-sub">I tuoi pazienti assegnati</div>
  </div>
  <span id="gira-orologio" style="font-size:0.75rem; color:var(--muted);"></span>
</div>

<?php if (empty($device_assegnati)): ?>
  <div class="card" style="text-align:center; padding:var(--space-2xl);">
    <p style="color:var(--muted); margin-bottom:var(--space-md);">Nessun paziente assegnato.</p>
    <p style="font-size:0.78rem; color:var(--muted);">Contatta il tuo amministratore per ricevere le assegnazioni.</p>
  </div>
<?php else: ?>
  <div class="table-stack">
    <?php foreach ($device_assegnati as $d): ?>
      <?php
      $offline   = $d['ultimo_contatto'] === null || ($d['minuti_silenzio'] ?? 999) > 10;
      $bed_color = 'var(--green)';
      $bed_label = 'OK';

      if ($d['alert_tipo'] === 'PULSANTE') {
        $bed_color = 'var(--red)';
        $bed_label = '🆘 SOS';
      } elseif ($d['alert_tipo'] === 'ROSSO') {
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
      <div class="table-row" data-device-id="<?= $d['id'] ?>" style="<?= $d['alert_tipo'] === 'PULSANTE' ? 'background:rgba(224,92,92,0.06);' : '' ?>">
        <span class="gira-stato-pill" style="width:56px; flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:2px;">
          <?php $bed_size = 44; include VIEW_PATH . 'layout/_bed_icon.php'; ?>
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
            <span class="gira-posizione" style="font-size:0.72rem; color:var(--text);"><?= $d['posizione'] ?></span>
          <?php elseif ($offline): ?>
            <span style="font-size:0.72rem; color:var(--muted);">Nessun segnale</span>
          <?php endif; ?>

          <?php if ($d['alert_minuti'] !== null): ?>
            <span class="gira-minuti" style="font-size:0.72rem; color:var(--muted);"><?= $d['alert_minuti'] ?> min</span>
          <?php endif; ?>

          <?php if ($d['stato_batt'] !== null): ?>
            <span class="gira-batteria" style="font-size:0.72rem; color:<?= $d['stato_batt'] < 20 ? 'var(--amber)' : 'var(--muted)' ?>">
              🔋 <?= $d['stato_batt'] ?>%
            </span>
          <?php endif; ?>
        </span>

        <?php if ($d['alert_id']): ?>
          <a href="<?= APP_URL ?>/alert/prendi-in-carico/<?= $d['alert_id'] ?>"
            class="btn btn--outline" style="font-size:0.68rem; padding:4px 10px; border-color:var(--amber); color:var(--amber);">
            Gestisci
          </a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

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
GiraPolling.avvia("dashboard-operatore");
</script>'; ?>

<?php include VIEW_PATH . 'layout/footer.php'; ?>