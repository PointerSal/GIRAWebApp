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

<!-- Filtri: struttura (select) + ruolo (bottoni) -->
<div style="display:inline-flex; flex-direction:column; align-items:flex-start; gap:var(--space-sm); margin-bottom:var(--space-lg);">

  <?php if (Auth::isSuperadmin() && !empty($strutture_map)): ?>
    <select id="filtro-struttura"
      onchange="location.href='<?= APP_URL ?>/utenti?id_struttura='+this.value+'&id_ruolo=<?= $filtro_ruolo ?>'"
      style="background:var(--surface); border:1px solid var(--border); color:var(--text);
                 font-family:var(--font-mono); font-size:0.72rem; padding:6px 12px;
                 border-radius:var(--radius-sm); cursor:pointer; width:100%;">
      <option value="0">Tutte le strutture</option>
      <?php foreach ($strutture_map as $sid => $snome): ?>
        <option value="<?= $sid ?>" <?= $filtro_struttura === (int)$sid ? 'selected' : '' ?>>
          <?= htmlspecialchars($snome) ?>
        </option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>

  <div class="flex-center gap-sm">
    <?php
    $ruoli_filtro = [
      0                => 'Tutti',
      RUOLO_SUPERADMIN => 'Superadmin',
      RUOLO_ADMIN      => 'Admin',
      RUOLO_MEDICO     => 'Medico',
      RUOLO_UTENTE     => 'Operatore',
    ];
    foreach ($ruoli_filtro as $rid => $rlabel):
      $url_ruolo = APP_URL . '/utenti?' .
        ($filtro_struttura ? 'id_struttura=' . $filtro_struttura . '&' : '') .
        'id_ruolo=' . $rid;
    ?>
      <a href="<?= $url_ruolo ?>"
        class="btn btn--outline"
        style="font-size:0.68rem; padding:4px 10px; <?= $filtro_ruolo === $rid ? 'color:var(--green); border-color:var(--green);' : '' ?>">
        <?= $rlabel ?>
      </a>
    <?php endforeach; ?>
  </div>

</div>

<!-- Lista utenti -->
<div class="table-stack">
  <?php if (empty($utenti)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">Nessun utente trovato.</span>
    </div>
  <?php else: ?>
    <?php foreach ($utenti as $u): ?>
      <div class="table-row">

        <span class="status-dot <?= $u['attivo'] ? 'status-dot--ok' : 'status-dot--warn' ?>"></span>

        <!-- Ruolo + Nome + Mail -->
        <span class="table-row__label">
          <strong><?= htmlspecialchars($u['nome'] . ' ' . $u['cognome']) ?></strong>
          <span class="pill pill--muted" style="font-size:0.6rem; padding:2px 7px; margin-left:6px; vertical-align:middle;">
            <?= htmlspecialchars($u['ruolo_nome']) ?>
          </span>
          <br />
          <span style="color:var(--muted); font-size:0.72rem;">
            <?= htmlspecialchars($u['mail']) ?>
          </span>
        </span>

        <!-- Strutture -->
        <?php if ($u['strutture']): ?>
          <span class="table-row__meta" style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
            title="<?= htmlspecialchars($u['strutture']) ?>">
            🏥 <?= htmlspecialchars($u['strutture']) ?>
          </span>
        <?php else: ?>
          <span class="table-row__meta" style="color:var(--muted);">— nessuna struttura</span>
        <?php endif; ?>

        <!-- Azioni: Device | Modifica | Password -->
        <div class="flex-center gap-sm">
          <?php if ((int)$u['id_ruolo'] !== RUOLO_SUPERADMIN): ?>
            <a href="<?= APP_URL ?>/utenti/device-assegnati/<?= $u['id'] ?>"
              class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px; color:var(--green); border-color:var(--green);">DEV</a>
          <?php else: ?>
            <span style="display:inline-block; width:52px;"></span><!-- placeholder allineamento -->
          <?php endif; ?>
          <a href="<?= APP_URL ?>/utenti/modifica/<?= $u['id'] ?>"
            class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">MOD</a>
          <a href="<?= APP_URL ?>/utenti/reset-pwd/<?= $u['id'] ?>"
            class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">PSW</a>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>