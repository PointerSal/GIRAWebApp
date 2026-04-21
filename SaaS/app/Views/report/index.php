<?php
// ============================================================
//  GIRA · app/Views/report/index.php
// ============================================================

// Helpers locali
function fmt_min(int $min): string
{
  if ($min <= 0) return '—';
  if ($min < 60) return $min . ' min';
  $h = intdiv($min, 60);
  $m = $min % 60;
  return $h . 'h' . ($m ? ' ' . $m . 'm' : '');
}
?>

<div class="page-header">
  <div>
    <h1>Report</h1>
    <div class="page-header-sub">
      <?= date('d/m/Y', strtotime($data_da)) ?> — <?= date('d/m/Y', strtotime($data_a)) ?>
      · <?= count($righe) ?> device
    </div>
  </div>
  <button class="btn btn--outline" style="opacity:0.45; cursor:not-allowed;" title="Disponibile con piano Plus" disabled>
    Esporta CSV
  </button>
</div>

<!-- ── Filtri ──────────────────────────────────────────────── -->
<form method="get" action="<?= APP_URL ?>/report" class="card" style="margin-bottom:var(--space-lg);">
  <table style="width:100%; border-collapse:collapse; table-layout:fixed;">

    <!-- Riga 1: Device su 4 colonne con optgroup per area -->
    <tr>
      <td colspan="4" style="padding:0 0 var(--space-md) 0; vertical-align:top;">
        <label class="form-label">Dispositivi</label>
        <select name="id_device[]" class="form-control" multiple size="8" style="height:auto; width:100%;">
          <?php foreach ($device as $dev): ?>
            <?php
            $parti = [];
            if ($dev['area'])    $parti[] = $dev['area'];
            if ($dev['subarea']) $parti[] = $dev['subarea'];
            $parti[] = $dev['label'] ?? $dev['mac'];
            $testo = implode(' · ', $parti);
            ?>
            <option value="<?= $dev['id'] ?>" <?= in_array($dev['id'], $filtro_device) ? 'selected' : '' ?>>
              <?= htmlspecialchars($testo) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span style="font-size:0.68rem; color:var(--muted);">Ctrl+click per selezionare più device</span>
      </td>
    </tr>

    <!-- Riga 2: Dal / Al + preset -->
    <tr>
      <td style="padding:0 var(--space-sm) var(--space-md) 0; vertical-align:top; width:25%;">
        <label class="form-label">Dal</label>
        <input type="date" name="da" class="form-control" value="<?= htmlspecialchars($data_da) ?>">
      </td>
      <td style="padding:0 var(--space-sm) var(--space-md) var(--space-sm); vertical-align:top; width:25%;">
        <label class="form-label">Al</label>
        <input type="date" name="a" class="form-control" value="<?= htmlspecialchars($data_a) ?>">
      </td>
      <td style="padding:0 var(--space-sm) var(--space-md) var(--space-sm); vertical-align:bottom; width:25%;">
        <?php
        $da7  = date('Y-m-d', strtotime('-7 days'));
        $da30 = date('Y-m-d', strtotime('-30 days'));
        $oggi = date('Y-m-d');
        $att7  = ($data_da === $da7  && $data_a === $oggi);
        $att30 = ($data_da === $da30 && $data_a === $oggi);
        ?>
        <a href="<?= APP_URL ?>/report?da=<?= $da7 ?>&a=<?= $oggi ?>&tipi[]=ROSSO&tipi[]=OFFLINE"
          class="btn btn--outline" style="width:100%; text-align:center; <?= $att7 ? 'color:var(--green); border-color:var(--green);' : '' ?>">
          Ultimi 7 gg
        </a>
      </td>
      <td style="padding:0 0 var(--space-md) var(--space-sm); vertical-align:bottom; width:25%;">
        <a href="<?= APP_URL ?>/report?da=<?= $da30 ?>&a=<?= $oggi ?>&tipi[]=ROSSO&tipi[]=OFFLINE"
          class="btn btn--outline" style="width:100%; text-align:center; <?= $att30 ? 'color:var(--green); border-color:var(--green);' : '' ?>">
          Ultimi 30 gg
        </a>
      </td>
    </tr>

    <!-- Riga 3: Tipo alert -->
    <tr>
      <td style="padding:0 var(--space-sm) var(--space-md) 0; vertical-align:middle;">
        <div style="display:flex; justify-content:flex-end; align-items:center; height:100%;">
          <input type="checkbox" name="tipi[]" value="ROSSO" <?= in_array('ROSSO', $filtro_tipi) ? 'checked' : '' ?> style="margin:0; cursor:pointer;">
        </div>
      </td>
      <td style="padding:0 var(--space-sm) var(--space-md) var(--space-sm); vertical-align:middle;">
        <label style="font-size:0.82rem; color:var(--red); cursor:pointer; line-height:1;">Alert rosso</label>
      </td>
      <td style="padding:0 var(--space-sm) var(--space-md) var(--space-sm); vertical-align:middle;">
        <div style="display:flex; justify-content:flex-end; align-items:center; height:100%;">
          <input type="checkbox" name="tipi[]" value="OFFLINE" <?= in_array('OFFLINE', $filtro_tipi) ? 'checked' : '' ?> style="margin:0; cursor:pointer;">
        </div>
      </td>
      <td style="padding:0 0 var(--space-md) var(--space-sm); vertical-align:middle;">
        <label style="font-size:0.82rem; color:var(--muted); cursor:pointer; line-height:1;">Offline</label>
      </td>
    </tr>

    <!-- Riga 4: Applica centrato su col 2-3 -->
    <tr>
      <td style="width:25%;"></td>
      <td colspan="2" style="padding:var(--space-md) var(--space-sm) 0; vertical-align:top;">
        <button type="submit" class="btn btn--primary" style="width:100%; text-align:center; justify-content:center;">Applica</button>
      </td>
      <td style="width:25%;"></td>
    </tr>

  </table>
</form>

<!-- ── Totali ──────────────────────────────────────────────── -->
<?php if (!empty($righe)): ?>
  <div class="stat-grid" style="margin-bottom:var(--space-lg);">
    <div class="stat-card">
      <div class="stat-label">Alert rossi</div>
      <div class="stat-value" style="color:<?= $totali['n_rossi'] > 0 ? 'var(--red)' : 'var(--green)' ?>">
        <?= $totali['n_rossi'] ?>
      </div>
      <div class="stat-sub"><?= fmt_min((int)$totali['min_rossi']) ?> totali</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Episodi offline</div>
      <div class="stat-value" style="color:<?= $totali['n_offline'] > 0 ? 'var(--amber)' : 'var(--green)' ?>">
        <?= $totali['n_offline'] ?>
      </div>
      <div class="stat-sub"><?= fmt_min((int)$totali['min_offline']) ?> totali</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Device monitorati</div>
      <div class="stat-value"><?= count($righe) ?></div>
      <div class="stat-sub">nel periodo</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Periodo</div>
      <div class="stat-value" style="font-size:1.1rem;">
        <?= (int)((strtotime($data_a) - strtotime($data_da)) / 86400) + 1 ?> gg
      </div>
      <div class="stat-sub"><?= date('d/m', strtotime($data_da)) ?> → <?= date('d/m', strtotime($data_a)) ?></div>
    </div>
  </div>
<?php endif; ?>

<!-- ── Tabella device ───────────────────────────────────────── -->
<div class="table-stack">

  <?php if (empty($righe)): ?>
    <div class="table-row" style="padding:var(--space-xl); justify-content:center;">
      <span style="color:var(--muted); font-size:0.9rem;">Nessun dato per il periodo selezionato.</span>
    </div>

  <?php else: ?>

    <!-- Header -->
    <div class="table-row" style="font-size:0.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid var(--border);">
      <span style="flex:2; min-width:140px;">Dispositivi</span>
      <?php if (count($strutture) > 1): ?>
        <span style="flex:1.5; min-width:120px;">Struttura</span>
      <?php endif; ?>
      <span style="flex:1; min-width:80px;">Reparto</span>
      <?php if (in_array('ROSSO', $filtro_tipi)): ?>
        <span style="flex:0.8; min-width:60px; text-align:center; color:var(--red);">Rossi</span>
        <span style="flex:0.8; min-width:70px; text-align:center; color:var(--red);">Tempo rosso</span>
        <span style="flex:1; min-width:80px; text-align:center; color:var(--red);">Posizione</span>
      <?php endif; ?>
      <?php if (in_array('OFFLINE', $filtro_tipi)): ?>
        <span style="flex:0.8; min-width:60px; text-align:center;">Offline</span>
        <span style="flex:0.8; min-width:70px; text-align:center;">Tempo off</span>
      <?php endif; ?>
      <span style="flex:0.6; min-width:40px;"></span>
    </div>

    <?php foreach ($righe as $r):
      $offline_now = $r['ultimo_contatto'] === null || ($r['minuti_silenzio'] ?? 999) > 10;
    ?>
      <div class="table-row">

        <!-- Device label + MAC -->
        <span class="table-row__label" style="flex:2; min-width:140px;">
          <strong><?= htmlspecialchars($r['label']) ?></strong>
          <span style="color:var(--muted); font-size:0.68rem; margin-left:6px; font-family:var(--font-mono);">
            <?= htmlspecialchars($r['mac']) ?>
          </span>
          <?php if ($offline_now): ?>
            <span style="font-size:0.65rem; color:var(--muted); margin-left:4px;">● offline</span>
          <?php endif; ?>
        </span>

        <!-- Struttura (solo se multi-struttura) -->
        <?php if (count($strutture) > 1): ?>
          <span class="table-row__meta" style="flex:1.5; min-width:120px;">
            <?= htmlspecialchars($r['struttura_nome']) ?>
          </span>
        <?php endif; ?>

        <!-- Reparto -->
        <span class="table-row__meta" style="flex:1; min-width:80px; font-size:0.72rem;">
          <?= $r['area'] ? htmlspecialchars($r['area'] . ($r['subarea'] ? ' · ' . $r['subarea'] : '')) : '<span style="color:var(--muted);">—</span>' ?>
        </span>

        <!-- Alert ROSSO -->
        <?php if (in_array('ROSSO', $filtro_tipi)): ?>
          <span style="flex:0.8; min-width:60px; text-align:center; font-size:0.82rem;
                     color:<?= (int)$r['n_rossi'] > 0 ? 'var(--red)' : 'var(--muted)' ?>">
            <?= (int)$r['n_rossi'] > 0 ? $r['n_rossi'] : '—' ?>
          </span>
          <span style="flex:0.8; min-width:70px; text-align:center; font-size:0.82rem;
                     color:<?= (int)$r['min_rossi'] > 0 ? 'var(--red)' : 'var(--muted)' ?>">
            <?= fmt_min((int)$r['min_rossi']) ?>
          </span>
          <span style="flex:1; min-width:80px; text-align:center; font-size:0.72rem; color:var(--muted);">
            <?= $r['pos_rosso'] ? htmlspecialchars($r['pos_rosso']) : '—' ?>
          </span>
        <?php endif; ?>

        <!-- Alert OFFLINE -->
        <?php if (in_array('OFFLINE', $filtro_tipi)): ?>
          <span style="flex:0.8; min-width:60px; text-align:center; font-size:0.82rem;
                     color:<?= (int)$r['n_offline'] > 0 ? 'var(--amber)' : 'var(--muted)' ?>">
            <?= (int)$r['n_offline'] > 0 ? $r['n_offline'] : '—' ?>
          </span>
          <span style="flex:0.8; min-width:70px; text-align:center; font-size:0.82rem;
                     color:<?= (int)$r['n_offline'] > 0 ? 'var(--amber)' : 'var(--muted)' ?>">
            <?= fmt_min((int)$r['min_offline']) ?>
          </span>
        <?php endif; ?>

        <!-- Link dettaglio device -->
        <span style="flex:0.6; min-width:40px; display:flex; justify-content:flex-end;">
          <?php if (!Auth::isMedico()): ?>
            <a href="<?= APP_URL ?>/device/show/<?= $r['id'] ?>" class="btn btn--outline btn--icon" title="Dettaglio device">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </a>
          <?php endif; ?>
        </span>

      </div>
    <?php endforeach; ?>

    <!-- Totali footer -->
    <div class="table-row" style="font-size:0.78rem; font-weight:500; border-top:1px solid var(--border); margin-top:4px; padding-top:8px;">
      <span style="flex:2; min-width:140px; color:var(--muted);">Totali</span>
      <?php if (count($strutture) > 1): ?>
        <span style="flex:1.5; min-width:120px;"></span>
      <?php endif; ?>
      <span style="flex:1; min-width:80px;"></span>
      <?php if (in_array('ROSSO', $filtro_tipi)): ?>
        <span style="flex:0.8; min-width:60px; text-align:center; color:<?= $totali['n_rossi'] > 0 ? 'var(--red)' : 'var(--muted)' ?>">
          <?= $totali['n_rossi'] ?: '—' ?>
        </span>
        <span style="flex:0.8; min-width:70px; text-align:center; color:<?= $totali['min_rossi'] > 0 ? 'var(--red)' : 'var(--muted)' ?>">
          <?= fmt_min((int)$totali['min_rossi']) ?>
        </span>
        <span style="flex:1; min-width:80px;"></span>
      <?php endif; ?>
      <?php if (in_array('OFFLINE', $filtro_tipi)): ?>
        <span style="flex:0.8; min-width:60px; text-align:center; color:<?= $totali['n_offline'] > 0 ? 'var(--amber)' : 'var(--muted)' ?>">
          <?= $totali['n_offline'] ?: '—' ?>
        </span>
        <span style="flex:0.8; min-width:70px; text-align:center; color:<?= $totali['n_offline'] > 0 ? 'var(--amber)' : 'var(--muted)' ?>">
          <?= fmt_min((int)$totali['min_offline']) ?>
        </span>
      <?php endif; ?>
      <span style="flex:0.6; min-width:40px;"></span>
    </div>

  <?php endif; ?>
</div>