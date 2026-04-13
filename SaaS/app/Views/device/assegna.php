<?php
// ============================================================
//  GIRA · app/Views/device/assegna.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Assegna ubicazione</h1>
    <div class="page-header-sub">
      <?= htmlspecialchars($device['label'] ?? $device['mac']) ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/device/show/<?= $device['id'] ?>"
     class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px;">
  <form action="<?= APP_URL ?>/device/assegna-post" method="POST">
    <input type="hidden" name="id" value="<?= $device['id'] ?>"/>

    <div class="form-group">
      <label for="id_ubicazione">Ubicazione</label>
      <select id="id_ubicazione" name="id_ubicazione">
        <option value="">— Non assegnata —</option>
        <?php foreach ($ubicazioni as $u): ?>
          <option value="<?= $u['id'] ?>"
            <?= (int)$device['id_ubicazione'] === (int)$u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['area'] . ($u['subarea'] ? ' · ' . $u['subarea'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if (empty($ubicazioni)): ?>
      <p style="font-size:0.78rem; color:var(--amber); margin-bottom:var(--space-md);">
        ⚠ Nessuna ubicazione definita per questa struttura.
        <a href="<?= APP_URL ?>/ubicazioni?id_struttura=<?= $device['id_struttura'] ?>">
          Aggiungine una →
        </a>
      </p>
    <?php endif; ?>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">Salva</button>
      <a href="<?= APP_URL ?>/device/show/<?= $device['id'] ?>"
         class="btn btn--outline">Annulla</a>
    </div>
  </form>
</div>
