<?php
// ============================================================
//  GIRA · app/Views/utenti/device_assegnati.php
// ============================================================

// Raggruppa per struttura
$per_struttura = [];
foreach ($device_disponibili as $d) {
  $per_struttura[$d['struttura_nome']][] = $d;
}
$multi_struttura = count($per_struttura) > 1;

// Raccogli aree uniche (per struttura)
$aree_per_struttura = [];
foreach ($device_disponibili as $d) {
  if ($d['area']) {
    $aree_per_struttura[$d['struttura_nome']][$d['area']] = true;
  }
}
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

    <!-- ── Filtri ─────────────────────────────────────────────── -->
    <div style="display:flex; gap:var(--space-sm); flex-wrap:wrap; margin-bottom:var(--space-lg);">

      <?php if ($multi_struttura): ?>
      <select id="filtro-struttura" onchange="applicaFiltri()"
        style="background:var(--surface); border:1px solid var(--border); color:var(--text);
               font-size:0.78rem; padding:5px 10px; border-radius:var(--radius-sm); cursor:pointer;">
        <option value="">Tutte le strutture</option>
        <?php foreach (array_keys($per_struttura) as $nome): ?>
          <option value="<?= htmlspecialchars($nome) ?>"><?= htmlspecialchars($nome) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <select id="filtro-area" onchange="applicaFiltri()"
        style="background:var(--surface); border:1px solid var(--border); color:var(--text);
               font-size:0.78rem; padding:5px 10px; border-radius:var(--radius-sm); cursor:pointer;">
        <option value="">Tutte le aree</option>
        <?php
        $aree_uniche = [];
        foreach ($device_disponibili as $d) {
          if ($d['area'] && !in_array($d['area'], $aree_uniche)) {
            $aree_uniche[] = $d['area'];
          }
        }
        foreach ($aree_uniche as $area):
        ?>
          <option value="<?= htmlspecialchars($area) ?>"><?= htmlspecialchars($area) ?></option>
        <?php endforeach; ?>
      </select>

      <select id="filtro-subarea" onchange="applicaFiltri()"
        style="background:var(--surface); border:1px solid var(--border); color:var(--text);
               font-size:0.78rem; padding:5px 10px; border-radius:var(--radius-sm); cursor:pointer;">
        <option value="">Tutte le subaree</option>
      </select>

    </div>

    <!-- ── Form ───────────────────────────────────────────────── -->
    <form action="<?= APP_URL ?>/utenti/device-assegnati-post" method="POST">
      <input type="hidden" name="id" value="<?= $target['id'] ?>" />

      <p class="section-label">Seleziona i device da monitorare</p>

      <?php foreach ($per_struttura as $nome_struttura => $devices): ?>
        <?php if ($multi_struttura): ?>
          <p style="font-size:0.7rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted); margin-bottom:var(--space-sm);">
            🏥 <?= htmlspecialchars($nome_struttura) ?>
          </p>
        <?php endif; ?>

        <?php foreach ($devices as $d): ?>
          <label class="device-row"
            data-struttura="<?= htmlspecialchars($d['struttura_nome']) ?>"
            data-area="<?= htmlspecialchars($d['area'] ?? '') ?>"
            data-subarea="<?= htmlspecialchars($d['subarea'] ?? '') ?>"
            style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:8px 0;
                   border-bottom:1px solid var(--border); font-size:0.85rem; color:var(--text);
                   text-transform:none; letter-spacing:0;">
            <input type="checkbox" name="device_ids[]" value="<?= $d['id'] ?>"
              <?= in_array((int)$d['id'], $ids_assegnati) ? 'checked' : '' ?>
              onchange="aggiornaContatore()"
              style="width:16px; height:16px; flex-shrink:0;" />
            <span style="flex:1;">
              <strong><?= htmlspecialchars($d['label'] ?? $d['mac']) ?></strong>
            </span>
            <?php if ($d['area']): ?>
              <span style="font-size:0.72rem; color:var(--muted);">
                <?= htmlspecialchars($d['area'] . ($d['subarea'] ? ' · ' . $d['subarea'] : '')) ?>
              </span>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>

      <?php endforeach; ?>

      <!-- ── Contatore + Submit ──────────────────────────────── -->
      <div class="flex-center gap-md mt-xl" style="justify-content:space-between; flex-wrap:wrap;">
        <span id="contatore-device" style="font-size:0.78rem; color:var(--muted);"></span>
        <div class="flex-center gap-md">
          <button type="submit" class="btn btn--primary">Salva assegnazioni</button>
          <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">Annulla</a>
        </div>
      </div>

    </form>
  </div>

  <script>
  // Dati subaree per cascata
  const subareePerArea = {};
  <?php foreach ($device_disponibili as $d): ?>
    <?php if ($d['area'] && $d['subarea']): ?>
    if (!subareePerArea[<?= json_encode($d['area']) ?>]) {
      subareePerArea[<?= json_encode($d['area']) ?>] = [];
    }
    if (!subareePerArea[<?= json_encode($d['area']) ?>].includes(<?= json_encode($d['subarea']) ?>)) {
      subareePerArea[<?= json_encode($d['area']) ?>].push(<?= json_encode($d['subarea']) ?>);
    }
    <?php endif; ?>
  <?php endforeach; ?>

  function applicaFiltri() {
    const struttura = document.getElementById('filtro-struttura')?.value ?? '';
    const area      = document.getElementById('filtro-area').value;
    const subarea   = document.getElementById('filtro-subarea').value;

    // Aggiorna subarea in cascata
    const filtroSubarea = document.getElementById('filtro-subarea');
    const subareeDisp   = area && subareePerArea[area] ? subareePerArea[area] : [];
    filtroSubarea.innerHTML = '<option value="">Tutte le subaree</option>';
    subareeDisp.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s;
      opt.textContent = s;
      if (s === subarea) opt.selected = true;
      filtroSubarea.appendChild(opt);
    });

    // Filtra righe
    document.querySelectorAll('.device-row').forEach(row => {
      const rStr = row.dataset.struttura;
      const rArea = row.dataset.area;
      const rSub  = row.dataset.subarea;

      const okStr = !struttura || rStr === struttura;
      const okArea = !area || rArea === area;
      const okSub  = !filtroSubarea.value || rSub === filtroSubarea.value;

      row.style.display = (okStr && okArea && okSub) ? 'flex' : 'none';
    });

    aggiornaContatore();
  }

  function aggiornaContatore() {
    const totali    = document.querySelectorAll('input[name="device_ids[]"]').length;
    const selezionati = document.querySelectorAll('input[name="device_ids[]"]:checked').length;
    document.getElementById('contatore-device').textContent =
      selezionati + ' device selezionat' + (selezionati === 1 ? 'o' : 'i') + ' su ' + totali + ' disponibili';
  }

  // Init
  document.addEventListener('DOMContentLoaded', aggiornaContatore);
  </script>

<?php endif; ?>
