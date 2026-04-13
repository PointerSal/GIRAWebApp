<?php include VIEW_PATH . 'layout/header.php'; ?>

<div class="page-header">
  <div>
    <h1>Bentornato, <?= htmlspecialchars($utente['nome']) ?></h1>
    <div class="page-header-sub">I tuoi pazienti assegnati</div>
  </div>
  <span style="font-size:0.75rem; color:var(--muted);"><?= date('d/m/Y H:i') ?></span>
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
      $pill_class = 'pill--ok';
      $pill_label = 'OK';
      $offline = ($d['minuti_silenzio'] ?? 999) > 10;

      if ($d['alert_tipo'] === 'PULSANTE') { $pill_class = 'pill--red';  $pill_label = '🆘 SOS'; }
      elseif ($d['alert_tipo'] === 'ROSSO')    { $pill_class = 'pill--red';  $pill_label = 'Rosso'; }
      elseif ($d['alert_tipo'] === 'ARANCIO')  { $pill_class = 'pill--warn'; $pill_label = 'Arancio'; }
      elseif ($offline)                        { $pill_class = 'pill--muted'; $pill_label = 'Offline'; }
    ?>
    <div class="table-row" style="<?= $d['alert_tipo'] === 'PULSANTE' ? 'background:rgba(224,92,92,0.06);' : '' ?>">
      <span class="pill <?= $pill_class ?>" style="width:72px; flex-shrink:0;"><?= $pill_label ?></span>

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
          <span style="font-size:0.72rem; color:var(--text);"><?= $d['posizione'] ?></span>
        <?php elseif ($offline): ?>
          <span style="font-size:0.72rem; color:var(--muted);">Nessun segnale</span>
        <?php endif; ?>

        <?php if ($d['alert_minuti'] !== null): ?>
          <span style="font-size:0.72rem; color:var(--muted);"><?= $d['alert_minuti'] ?> min</span>
        <?php endif; ?>

        <?php if ($d['stato_batt'] !== null): ?>
          <span style="font-size:0.72rem; color:<?= $d['stato_batt'] < 20 ? 'var(--amber)' : 'var(--muted)' ?>">
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

<?php include VIEW_PATH . 'layout/footer.php'; ?>
