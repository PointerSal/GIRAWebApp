<?php
// ============================================================
//  GIRA · app/Views/utenti/reset_pwd.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Reset password</h1>
    <div class="page-header-sub">
      <?= htmlspecialchars($target['nome'] . ' ' . $target['cognome']) ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:400px;">
  <form action="<?= APP_URL ?>/utenti/reset-pwd-post" method="POST">
    <input type="hidden" name="id" value="<?= $target['id'] ?>"/>

    <div class="form-group">
      <label for="password">Nuova password temporanea</label>
      <input type="password" id="password" name="password"
             placeholder="Minimo 8 caratteri"
             autocomplete="new-password"
             required/>
      <span style="font-size:0.7rem; color:var(--muted);">
        L'utente dovrà cambiarla al prossimo accesso.
      </span>
    </div>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">Reimposta password</button>
      <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">Annulla</a>
    </div>
  </form>
</div>
