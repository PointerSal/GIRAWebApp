<?php
// ============================================================
//  GIRA · app/Views/ubicazioni/index.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Ubicazioni</h1>
    <div class="page-header-sub"><?= htmlspecialchars($struttura['ragione_sociale']) ?></div>
  </div>
  <div class="flex-center gap-sm">
    <a href="<?= APP_URL ?>/strutture/show/<?= $id_struttura ?>"
       class="btn btn--outline">← Struttura</a>
    <a href="<?= APP_URL ?>/ubicazioni/crea?id_struttura=<?= $id_struttura ?>"
       class="btn btn--primary">+ Nuova ubicazione</a>
  </div>
</div>

<div class="table-stack">
  <?php if (empty($ubicazioni)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">
        Nessuna ubicazione definita. Aggiungine una per poter assegnare i device.
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
             onclick="return confirm('Eliminare questa ubicazione?')">Elimina</a>
        <?php else: ?>
          <span class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px; opacity:0.4; cursor:not-allowed;"
                title="Ha device assegnati">Elimina</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
