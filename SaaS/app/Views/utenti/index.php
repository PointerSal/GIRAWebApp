<?php
// ============================================================
//  GIRA · app/Views/utenti/index.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Utenti</h1>
    <div class="page-header-sub"><?= count($utenti) ?> utenti registrati</div>
  </div>
  <a href="<?= APP_URL ?>/utenti/crea" class="btn btn--primary">+ Nuovo utente</a>
</div>

<!-- Filtro struttura (solo superadmin) -->
<?php if (Auth::isSuperadmin() && !empty($strutture_map)): ?>
<div class="flex-center gap-sm" style="margin-bottom:var(--space-lg); flex-wrap:wrap;">
  <a href="<?= APP_URL ?>/utenti"
     class="btn btn--outline"
     <?= !$filtro_struttura ? 'style="color:var(--green); border-color:var(--green);"' : '' ?>>
    Tutti
  </a>
  <?php foreach ($strutture_map as $sid => $snome): ?>
    <a href="<?= APP_URL ?>/utenti?id_struttura=<?= $sid ?>"
       class="btn btn--outline"
       <?= $filtro_struttura === (int)$sid ? 'style="color:var(--green); border-color:var(--green);"' : '' ?>>
      <?= htmlspecialchars($snome) ?>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Lista utenti -->
<div class="table-stack">
  <?php if (empty($utenti)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">Nessun utente trovato.</span>
    </div>
  <?php else: ?>
    <?php foreach ($utenti as $u): ?>
    <div class="table-row">

      <span class="status-dot <?= $u['attivo'] ? 'status-dot--ok' : 'status-dot--off' ?>"></span>

      <span class="table-row__label">
        <strong><?= htmlspecialchars($u['nome'] . ' ' . $u['cognome']) ?></strong>
        <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
          <?= htmlspecialchars($u['mail']) ?>
        </span>
      </span>

      <span class="pill pill--muted"><?= htmlspecialchars($u['ruolo_nome']) ?></span>

      <?php if ($u['strutture']): ?>
        <span class="table-row__meta" style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
              title="<?= htmlspecialchars($u['strutture']) ?>">
          🏥 <?= htmlspecialchars($u['strutture']) ?>
        </span>
      <?php else: ?>
        <span class="table-row__meta" style="color:var(--muted);">— nessuna struttura</span>
      <?php endif; ?>

      <div class="flex-center gap-sm">
        <a href="<?= APP_URL ?>/utenti/modifica/<?= $u['id'] ?>"
           class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">Modifica</a>
        <a href="<?= APP_URL ?>/utenti/reset-pwd/<?= $u['id'] ?>"
           class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">Password</a>
        <?php if ((int)$u['id_ruolo'] === RUOLO_UTENTE): ?>
          <a href="<?= APP_URL ?>/utenti/device-assegnati/<?= $u['id'] ?>"
             class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">Device</a>
        <?php endif; ?>
        <?php if ($u['id'] !== Auth::id()): ?>
          <a href="<?= APP_URL ?>/utenti/elimina/<?= $u['id'] ?>"
             class="btn btn--danger" style="font-size:0.68rem; padding:3px 10px;"
             onclick="return confirm('Disattivare questo utente?')">Disattiva</a>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
