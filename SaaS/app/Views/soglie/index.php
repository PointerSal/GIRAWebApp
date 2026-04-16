<?php
// ============================================================
//  GIRA · app/Views/soglie/index.php
//  Configurazione soglie alert per admin
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Soglie alert</h1>
    <div class="page-header-sub"><?= htmlspecialchars($struttura['ragione_sociale']) ?></div>
  </div>
</div>

<?php if (!empty($successo)): ?>
  <div class="alert-flash alert-flash--ok"><?= htmlspecialchars($successo) ?></div>
<?php endif; ?>
<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form action="<?= APP_URL ?>/soglie/salva" method="POST">

  <div class="card" style="max-width:500px; margin-bottom:var(--space-xl);">
    <p class="section-label">Soglie immobilità</p>
    <p style="font-size:0.78rem; color:var(--muted); margin-bottom:var(--space-lg);">
      Minuti di immobilità prima che scatti l'alert.
      La soglia rossa deve essere almeno 5 minuti maggiore della soglia arancio.
    </p>

    <div class="form-group">
      <label for="soglia_arancio_min">🟠 Soglia arancio (minuti)</label>
      <input type="number" id="soglia_arancio_min" name="soglia_arancio_min"
             value="<?= $soglie['soglia_arancio_min'] ?>"
             min="<?= $soglie['arancio_min_min'] ?>"
             max="<?= $soglie['arancio_min_max'] ?>"
             required/>
      <span style="font-size:0.7rem; color:var(--muted);">
        Range consentito: <?= $soglie['arancio_min_min'] ?>–<?= $soglie['arancio_min_max'] ?> minuti
      </span>
    </div>

    <div class="form-group">
      <label for="soglia_rosso_min">🔴 Soglia rossa (minuti)</label>
      <input type="number" id="soglia_rosso_min" name="soglia_rosso_min"
             value="<?= $soglie['soglia_rosso_min'] ?>"
             min="<?= $soglie['rosso_min_min'] ?>"
             max="<?= $soglie['rosso_min_max'] ?>"
             required/>
      <span style="font-size:0.7rem; color:var(--muted);">
        Range consentito: <?= $soglie['rosso_min_min'] ?>–<?= $soglie['rosso_min_max'] ?> minuti
      </span>
    </div>
  </div>

  <!-- Silenzio notturno — modificabile entro i limiti del superadmin -->
  <div class="card" style="max-width:500px; margin-bottom:var(--space-xl);">
    <p class="section-label">Silenzio notturno</p>
    <p style="font-size:0.78rem; color:var(--muted); margin-bottom:var(--space-lg);">
      Durante queste ore non vengono generati alert di immobilità.
      Gli alert SOS e batteria rimangono sempre attivi.
    </p>
    <div class="grid grid--2" style="gap:var(--space-lg); background:transparent; border:none;">
      <div class="form-group" style="background:transparent;">
        <label for="silenzio_da">🌙 Inizio silenzio</label>
        <select id="silenzio_da" name="silenzio_da">
          <?php
            $sda_min = (int)($soglie['silenzio_da_min'] ?? 20);
            $sda_max = (int)($soglie['silenzio_da_max'] ?? 23);
            for ($h = $sda_min; $h <= $sda_max; $h++):
          ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_da'] ?? 22) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
        <span style="font-size:0.7rem; color:var(--muted);">
          Range consentito: <?= sprintf('%02d:00', $sda_min) ?>–<?= sprintf('%02d:00', $sda_max) ?>
        </span>
      </div>
      <div class="form-group" style="background:transparent;">
        <label for="silenzio_a">☀️ Fine silenzio</label>
        <select id="silenzio_a" name="silenzio_a">
          <?php
            $sa_min = (int)($soglie['silenzio_a_min'] ?? 5);
            $sa_max = (int)($soglie['silenzio_a_max'] ?? 9);
            for ($h = $sa_min; $h <= $sa_max; $h++):
          ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_a'] ?? 7) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
        <span style="font-size:0.7rem; color:var(--muted);">
          Range consentito: <?= sprintf('%02d:00', $sa_min) ?>–<?= sprintf('%02d:00', $sa_max) ?>
        </span>
      </div>
    </div>
  </div>

  <div class="flex-center gap-md">
    <button type="submit" class="btn btn--primary">Salva soglie</button>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn--outline">Annulla</a>
  </div>

</form>

<script>
// Validazione client-side — aggiorna il min del campo rosso dinamicamente
document.getElementById('soglia_arancio_min').addEventListener('input', function() {
    const arancio = parseInt(this.value) || 0;
    const rossoInput = document.getElementById('soglia_rosso_min');
    rossoInput.min = arancio + 5;
    if (parseInt(rossoInput.value) < arancio + 5) {
        rossoInput.value = arancio + 5;
    }
});
</script>
