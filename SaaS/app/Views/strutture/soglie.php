<?php
// ============================================================
//  GIRA · app/Views/strutture/soglie.php
//  Configurazione soglie alert e silenzio notturno
//  Solo superadmin
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Soglie alert</h1>
    <div class="page-header-sub"><?= htmlspecialchars($struttura['ragione_sociale']) ?></div>
  </div>
  <a href="<?= APP_URL ?>/strutture/show/<?= $struttura['id'] ?>" class="btn btn--outline">← Dettaglio</a>
</div>

<?php if (!empty($successo)): ?>
  <div class="alert-flash alert-flash--ok"><?= htmlspecialchars($successo) ?></div>
<?php endif; ?>
<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form action="<?= APP_URL ?>/strutture/soglie-post" method="POST">
  <input type="hidden" name="id_struttura" value="<?= $struttura['id'] ?>"/>

  <!-- Soglie immobilità -->
  <div class="card" style="max-width:580px; margin-bottom:var(--space-xl);">
    <p class="section-label">Soglie immobilità</p>
    <p style="font-size:0.78rem; color:var(--muted); margin-bottom:var(--space-lg);">
      Minuti di immobilità prima che scatti l'alert. La soglia rossa deve essere
      almeno 5 minuti maggiore della soglia arancio.
    </p>

    <div class="grid grid--2" style="gap:var(--space-lg); background:transparent; border:none;">
      <div class="form-group" style="background:transparent;">
        <label for="soglia_arancio_min">🟠 Soglia arancio (minuti)</label>
        <input type="number" id="soglia_arancio_min" name="soglia_arancio_min"
               value="<?= $soglie['soglia_arancio_min'] ?? 20 ?>"
               min="<?= $soglie['arancio_min_min'] ?? 10 ?>"
               max="<?= $soglie['arancio_min_max'] ?? 30 ?>"
               required/>
        <span style="font-size:0.7rem; color:var(--muted);">
          Range consentito: <?= $soglie['arancio_min_min'] ?? 10 ?>–<?= $soglie['arancio_min_max'] ?? 30 ?> min
        </span>
      </div>
      <div class="form-group" style="background:transparent;">
        <label for="soglia_rosso_min">🔴 Soglia rossa (minuti)</label>
        <input type="number" id="soglia_rosso_min" name="soglia_rosso_min"
               value="<?= $soglie['soglia_rosso_min'] ?? 30 ?>"
               min="<?= $soglie['rosso_min_min'] ?? 20 ?>"
               max="<?= $soglie['rosso_min_max'] ?? 60 ?>"
               required/>
        <span style="font-size:0.7rem; color:var(--muted);">
          Range consentito: <?= $soglie['rosso_min_min'] ?? 20 ?>–<?= $soglie['rosso_min_max'] ?? 60 ?> min
        </span>
      </div>
    </div>
  </div>

  <!-- Silenzio notturno -->
  <div class="card" style="max-width:580px; margin-bottom:var(--space-xl);">
    <p class="section-label">Silenzio notturno</p>
    <p style="font-size:0.78rem; color:var(--muted); margin-bottom:var(--space-lg);">
      Durante queste ore non vengono generati alert di immobilità.
      Gli alert SOS e batteria rimangono sempre attivi.
    </p>

    <div class="grid grid--2" style="gap:var(--space-lg); background:transparent; border:none;">
      <div class="form-group" style="background:transparent;">
        <label for="silenzio_da">🌙 Inizio silenzio (ora)</label>
        <select id="silenzio_da" name="silenzio_da">
          <?php for ($h = 0; $h < 24; $h++): ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_da'] ?? 22) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="background:transparent;">
        <label for="silenzio_a">☀️ Fine silenzio (ora)</label>
        <select id="silenzio_a" name="silenzio_a">
          <?php for ($h = 0; $h < 24; $h++): ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_a'] ?? 7) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Range consentiti agli admin -->
  <div class="card" style="max-width:580px; margin-bottom:var(--space-xl);">
    <p class="section-label">Range consentiti agli admin</p>
    <p style="font-size:0.78rem; color:var(--muted); margin-bottom:var(--space-lg);">
      Limiti entro cui gli admin della struttura possono modificare le soglie.
    </p>

    <p style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:var(--space-sm);">Soglie immobilità</p>
    <div class="grid grid--2" style="gap:var(--space-lg); background:transparent; border:none; margin-bottom:var(--space-lg);">
      <div class="form-group" style="background:transparent;">
        <label>🟠 Arancio — minimo</label>
        <input type="number" name="arancio_min_min"
               value="<?= $soglie['arancio_min_min'] ?? 10 ?>" min="1" max="60" required/>
      </div>
      <div class="form-group" style="background:transparent;">
        <label>🟠 Arancio — massimo</label>
        <input type="number" name="arancio_min_max"
               value="<?= $soglie['arancio_min_max'] ?? 30 ?>" min="1" max="60" required/>
      </div>
      <div class="form-group" style="background:transparent;">
        <label>🔴 Rosso — minimo</label>
        <input type="number" name="rosso_min_min"
               value="<?= $soglie['rosso_min_min'] ?? 20 ?>" min="1" max="120" required/>
      </div>
      <div class="form-group" style="background:transparent;">
        <label>🔴 Rosso — massimo</label>
        <input type="number" name="rosso_min_max"
               value="<?= $soglie['rosso_min_max'] ?? 60 ?>" min="1" max="120" required/>
      </div>
    </div>

    <p style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:var(--space-sm);">Silenzio notturno</p>
    <div class="grid grid--2" style="gap:var(--space-lg); background:transparent; border:none;">
      <div class="form-group" style="background:transparent;">
        <label>🌙 Inizio silenzio — minimo</label>
        <select name="silenzio_da_min">
          <?php for ($h = 18; $h < 24; $h++): ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_da_min'] ?? 20) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="background:transparent;">
        <label>🌙 Inizio silenzio — massimo</label>
        <select name="silenzio_da_max">
          <?php for ($h = 18; $h < 24; $h++): ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_da_max'] ?? 23) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="background:transparent;">
        <label>☀️ Fine silenzio — minimo</label>
        <select name="silenzio_a_min">
          <?php for ($h = 4; $h <= 10; $h++): ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_a_min'] ?? 5) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="background:transparent;">
        <label>☀️ Fine silenzio — massimo</label>
        <select name="silenzio_a_max">
          <?php for ($h = 4; $h <= 10; $h++): ?>
            <option value="<?= $h ?>" <?= ($soglie['silenzio_a_max'] ?? 9) == $h ? 'selected' : '' ?>>
              <?= sprintf('%02d:00', $h) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="flex-center gap-md">
    <button type="submit" class="btn btn--primary">Salva soglie</button>
    <a href="<?= APP_URL ?>/strutture/show/<?= $struttura['id'] ?>" class="btn btn--outline">Annulla</a>
  </div>

</form>
