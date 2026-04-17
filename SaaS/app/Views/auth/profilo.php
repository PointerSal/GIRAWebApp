<?php include VIEW_PATH . 'layout/header.php'; ?>

<div class="page-header">
  <div>
    <h1>Il mio profilo</h1>
    <div class="page-header-sub"><?= htmlspecialchars($utente['mail']) ?></div>
  </div>
</div>

<?php if (!empty($successo)): ?>
  <div class="alert-flash alert-flash--ok"><?= htmlspecialchars($successo) ?></div>
<?php endif; ?>
<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<!-- Dati personali -->
<div class="card" style="max-width:500px; margin-bottom:var(--space-xl);">
  <p class="section-label">Dati personali</p>

  <form action="<?= APP_URL ?>/auth/profilo-post" method="POST">

    <div class="grid grid--2" style="gap:var(--space-md); background:transparent; border:none;">
      <div class="form-group" style="background:transparent;">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome"
               value="<?= htmlspecialchars($utente['nome']) ?>"
               required autofocus/>
      </div>
      <div class="form-group" style="background:transparent;">
        <label for="cognome">Cognome</label>
        <input type="text" id="cognome" name="cognome"
               value="<?= htmlspecialchars($utente['cognome']) ?>"
               required/>
      </div>
    </div>

    <div class="form-group">
      <label for="telefono">Telefono</label>
      <input type="tel" id="telefono" name="telefono"
             value="<?= htmlspecialchars($utente['telefono'] ?? '') ?>"
             placeholder="+39 333 1234567"/>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" value="<?= htmlspecialchars($utente['mail']) ?>"
             disabled style="opacity:0.5;"/>
      <span style="font-size:0.7rem; color:var(--muted);">
        L'email non può essere modificata da qui. Contatta l'amministratore.
      </span>
    </div>

    <div class="form-group">
      <label>Ruolo</label>
      <input type="text" value="<?= htmlspecialchars($utente['ruolo_nome'] ?? '') ?>"
             disabled style="opacity:0.5;"/>
    </div>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">Salva modifiche</button>
      <a href="<?= APP_URL ?>/dashboard" class="btn btn--outline">Annulla</a>
    </div>

  </form>
</div>

<!-- Cambio password -->
<div class="card" style="max-width:500px;">
  <p class="section-label">Sicurezza</p>
  <p style="font-size:0.82rem; color:var(--muted); margin-bottom:var(--space-md);">
    Vuoi cambiare la tua password?
  </p>
  <a href="<?= APP_URL ?>/auth/cambia-password" class="btn btn--outline">
    🔑 Cambia password
  </a>
</div>

<?php include VIEW_PATH . 'layout/footer.php'; ?>
