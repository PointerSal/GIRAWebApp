<?php
// ============================================================
//  GIRA · app/Views/ubicazioni/index.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Reparti</h1>
    <div class="page-header-sub"><?= htmlspecialchars($struttura['ragione_sociale']) ?></div>
  </div>
  <div class="flex-center gap-sm">
    <?php if (Auth::isSuperadmin()): ?>
      <a href="<?= APP_URL ?>/strutture/show/<?= $struttura['id'] ?>" class="btn btn--outline">← Struttura</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/ubicazioni/crea?id_struttura=<?= $id_struttura ?>"
      class="btn btn--primary">+ Nuovo reparto</a>
  </div>
</div>

<div class="table-stack">
  <?php if (empty($ubicazioni)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">
        Nessun reparto definito. Aggiungine uno per poter assegnare i dispositivi.
      </span>
    </div>
  <?php else: ?>
    <?php foreach ($ubicazioni as $u): ?>
      <div class="table-row">
        <span class="table-row__label">
          <strong><?= htmlspecialchars($u['area']) ?></strong>
          <?php if ($u['subarea']): ?>
            <span style="color:var(--muted); margin-left:8px; font-size:0.78rem;">
              · <?= htmlspecialchars($u['subarea']) ?>
            </span>
          <?php endif; ?>
        </span>
        <span class="table-row__meta">
          📡 <?= $u['tot_device'] ?> device
        </span>
        <div class="flex-center gap-sm">
          <a href="<?= APP_URL ?>/ubicazioni/modifica/<?= $u['id'] ?>"
            class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">Modifica</a>
          <?php if ($u['tot_device'] == 0): ?>
            <a href="<?= APP_URL ?>/ubicazioni/elimina/<?= $u['id'] ?>"
              class="btn btn--danger" style="font-size:0.68rem; padding:3px 10px;"
              onclick="return confirm('Eliminare questo reparto?')">Elimina</a>
          <?php else: ?>
            <span class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px; opacity:0.4; cursor:not-allowed;"
              title="Ha device assegnati">Elimina</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>