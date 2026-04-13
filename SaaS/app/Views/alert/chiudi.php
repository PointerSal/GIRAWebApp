<?php
// ============================================================
//  GIRA · app/Views/alert/chiudi.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Chiudi alert</h1>
    <div class="page-header-sub">
      <?= htmlspecialchars($alert['label'] ?? $alert['mac']) ?>
      — <?= ucfirst(strtolower($alert['tipo'])) ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/alert" class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px;">

  <!-- Info alert -->
  <div class="table-stack" style="margin-bottom:var(--space-lg);">
    <div class="table-row">
      <span style="color:var(--muted); font-size:0.75rem; width:100px;">Tipo</span>
      <?php
        $pill = match($alert['tipo']) {
          'ROSSO','PULSANTE' => 'pill--red',
          'ARANCIO','BATTERIA' => 'pill--warn',
          default => 'pill--muted',
        };
      ?>
      <span class="pill <?= $pill ?>"><?= $alert['tipo'] ?></span>
    </div>
    <div class="table-row">
      <span style="color:var(--muted); font-size:0.75rem; width:100px;">Aperto alle</span>
      <span><?= date('d/m/Y H:i', strtotime($alert['aperto_alle'])) ?></span>
    </div>
    <div class="table-row">
      <span style="color:var(--muted); font-size:0.75rem; width:100px;">Durata</span>
      <span><?= (int)((time() - strtotime($alert['aperto_alle'])) / 60) ?> minuti</span>
    </div>
  </div>

  <form action="<?= APP_URL ?>/alert/chiudi-post" method="POST">
    <input type="hidden" name="id" value="<?= $alert['id'] ?>"/>

    <div class="form-group">
      <label for="note">Note (opzionale)</label>
      <textarea id="note" name="note" rows="3"
                placeholder="Es: Paziente riposizionato dall'operatore..."
                style="resize:vertical;"></textarea>
    </div>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">Chiudi alert</button>
      <a href="<?= APP_URL ?>/alert" class="btn btn--outline">Annulla</a>
    </div>
  </form>
</div>
