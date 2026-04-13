<?php
// ============================================================
//  GIRA · app/Views/utenti/device_assegnati.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Device assegnati</h1>
    <div class="page-header-sub">
      Operatore: <?= htmlspecialchars($target['nome'] . ' ' . $target['cognome']) ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">← Utenti</a>
</div>

<?php if (!empty($successo)): ?>
  <div class="alert-flash alert-flash--ok"><?= htmlspecialchars($successo) ?></div>
<?php endif; ?>
<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<?php if (empty($device_disponibili)): ?>
  <div class="card" style="text-align:center; padding:var(--space-2xl);">
    <p style="color:var(--muted);">Nessun device disponibile per le strutture associate a questo operatore.</p>
  </div>
<?php else: ?>

<div class="card" style="max-width:600px;">
  <form action="<?= APP_URL ?>/utenti/device-assegnati-post" method="POST">
    <input type="hidden" name="id" value="<?= $target['id'] ?>"/>

    <p class="section-label">Seleziona i device da monitorare</p>

    <?php
      // Raggruppa per struttura
      $per_struttura = [];
      foreach ($device_disponibili as $d) {
          $per_struttura[$d['struttura_nome']][] = $d;
      }
    ?>

    <?php foreach ($per_struttura as $nome_struttura => $devices): ?>
      <div style="margin-bottom:var(--space-lg);">
        <p style="font-size:0.7rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted); margin-bottom:var(--space-sm);">
          🏥 <?= htmlspecialchars($nome_struttura) ?>
        </p>
        <?php foreach ($devices as $d): ?>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:8px 0; border-bottom:1px solid var(--border); font-size:0.85rem; color:var(--text); text-transform:none; letter-spacing:0;">
            <input type="checkbox" name="device_ids[]" value="<?= $d['id'] ?>"
                   <?= in_array((int)$d['id'], $ids_assegnati) ? 'checked' : '' ?>
                   style="width:16px; height:16px; flex-shrink:0;"/>
            <span style="flex:1;">
              <strong><?= htmlspecialchars($d['label'] ?? $d['mac']) ?></strong>
              <span style="font-family:var(--font-mono); font-size:0.7rem; color:var(--muted); margin-left:8px;">
                <?= htmlspecialchars($d['mac']) ?>
              </span>
            </span>
            <?php if ($d['area']): ?>
              <span style="font-size:0.72rem; color:var(--muted);">
                <?= htmlspecialchars($d['area'] . ($d['subarea'] ? ' · ' . $d['subarea'] : '')) ?>
              </span>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">Salva assegnazioni</button>
      <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">Annulla</a>
    </div>

  </form>
</div>

<?php endif; ?>
