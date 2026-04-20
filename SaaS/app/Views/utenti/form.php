<?php
// ============================================================
//  GIRA · app/Views/utenti/form.php
// ============================================================
$edit = isset($utente_target);
$d    = $form_data;
?>

<div class="page-header">
  <div>
    <h1><?= $edit ? 'Modifica utente' : 'Nuovo utente' ?></h1>
    <div class="page-header-sub">
      <?= $edit ? htmlspecialchars($utente_target['nome'] . ' ' . $utente_target['cognome']) : 'Compila i dati del nuovo utente' ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:580px;">
  <form action="<?= APP_URL ?>/utenti/<?= $edit ? 'modifica-post' : 'crea-post' ?>" method="POST">

    <?php if ($edit): ?>
      <input type="hidden" name="id" value="<?= $utente_target['id'] ?>" />
    <?php endif; ?>

    <!-- Nome + Cognome -->
    <div class="grid grid--2" style="gap:var(--space-md); background:transparent; border:none;">
      <div class="form-group" style="background:transparent;">
        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome"
          value="<?= htmlspecialchars($d['nome'] ?? '') ?>"
          required autofocus />
      </div>
      <div class="form-group" style="background:transparent;">
        <label for="cognome">Cognome *</label>
        <input type="text" id="cognome" name="cognome"
          value="<?= htmlspecialchars($d['cognome'] ?? '') ?>"
          required />
      </div>
    </div>

    <!-- Email -->
    <div class="form-group">
      <label for="mail">Email * (usata per il login)</label>
      <input type="email" id="mail" name="mail"
        value="<?= htmlspecialchars($d['mail'] ?? '') ?>"
        placeholder="nome@struttura.it"
        required autocomplete="off" />
    </div>

    <!-- Telefono -->
    <div class="form-group">
      <label for="telefono">Telefono</label>
      <input type="tel" id="telefono" name="telefono"
        value="<?= htmlspecialchars($d['telefono'] ?? '') ?>"
        placeholder="+39 333 1234567" />
    </div>

    <!-- Password (solo in creazione) -->
    <?php if (!$edit): ?>
      <div class="form-group">
        <label for="password">Password temporanea *</label>
        <input type="password" id="password" name="password"
          placeholder="Minimo 8 caratteri"
          autocomplete="new-password"
          required />
        <span style="font-size:0.7rem; color:var(--muted);">
          L'utente dovrà cambiarla al primo accesso.
        </span>
      </div>
    <?php endif; ?>

    <!-- Ruolo -->
    <div class="form-group">
      <label for="id_ruolo">Ruolo *</label>
      <select id="id_ruolo" name="id_ruolo" required>
        <?php foreach ($ruoli as $r): ?>
          <option value="<?= $r['id'] ?>"
            <?= (int)($d['id_ruolo'] ?? RUOLO_UTENTE) === (int)$r['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst($r['nome'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Strutture -->
    <div class="form-group">
      <label>Strutture associate</label>
      <?php if (empty($strutture)): ?>
        <p style="font-size:0.78rem; color:var(--amber);">
          ⚠ Nessuna struttura disponibile.
        </p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:6px; margin-top:4px;">
          <?php
          $ids_sel = (array)($d['strutture_ids'] ?? []);
          ?>
          <?php foreach ($strutture as $s): ?>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.82rem; color:var(--text); text-transform:none; letter-spacing:0;">
              <input type="checkbox" name="strutture_ids[]" value="<?= $s['id'] ?>"
                <?= in_array((int)$s['id'], array_map('intval', $ids_sel)) ? 'checked' : '' ?>
                style="width:16px; height:16px;" />
              <?= htmlspecialchars($s['ragione_sociale']) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <span style="font-size:0.7rem; color:var(--muted); margin-top:4px; display:block;">
          Il superadmin non ha bisogno di strutture — accede a tutto.
        </span>
      <?php endif; ?>
    </div>

    <?php if ($edit): ?>
      <!-- Stato attivo -->
      <div class="form-group">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
          <input type="checkbox" name="attivo" value="1"
            <?= $utente_target['attivo'] ? 'checked' : '' ?>
            style="width:16px; height:16px;" />
          Account attivo
        </label>
      </div>
    <?php endif; ?>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">
        <?= $edit ? 'Salva modifiche' : 'Crea utente' ?>
      </button>
      <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">Annulla</a>
    </div>

    <?php if ($edit): ?>
      <div class="flex-center gap-sm" style="margin-top:var(--space-lg); padding-top:var(--space-lg); border-top:1px solid var(--border);">
        <a href="<?= APP_URL ?>/utenti/preferenze/<?= $utente_target['id'] ?>"
          class="btn btn--outline" style="font-size:0.78rem;">
          🔔 Notifiche
        </a>
        <a href="<?= APP_URL ?>/utenti/reset-pwd/<?= $utente_target['id'] ?>"
          class="btn btn--outline" style="font-size:0.78rem;">
          🔑 Password
        </a>
      </div>
    <?php endif; ?>

  </form>
</div>