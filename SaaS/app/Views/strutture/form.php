<?php
// ============================================================
//  GIRA · app/Views/strutture/form.php
//  Usato sia per crea che per modifica
// ============================================================
$edit = isset($struttura); // true se siamo in modifica
$d    = $form_data;        // dati precompilati (da POST o da DB)
?>

<div class="page-header">
  <div>
    <h1><?= $edit ? 'Modifica struttura' : 'Nuova struttura' ?></h1>
    <div class="page-header-sub">
      <?= $edit ? htmlspecialchars($struttura['ragione_sociale']) : 'Compila i dati della struttura RSA' ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/strutture<?= $edit ? '/show/' . $struttura['id'] : '' ?>"
     class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:600px;">
  <form action="<?= APP_URL ?>/strutture/<?= $edit ? 'modifica-post' : 'crea-post' ?>" method="POST">

    <?php if ($edit): ?>
      <input type="hidden" name="id" value="<?= $struttura['id'] ?>"/>
    <?php endif; ?>

    <div class="form-group">
      <label for="ragione_sociale">Ragione sociale *</label>
      <input type="text" id="ragione_sociale" name="ragione_sociale"
             value="<?= htmlspecialchars($d['ragione_sociale'] ?? '') ?>"
             placeholder="Es: RSA Villa Serena" required autofocus/>
    </div>

    <div class="form-group">
      <label for="partita_iva">Partita IVA * (11 cifre)</label>
      <input type="text" id="partita_iva" name="partita_iva"
             value="<?= htmlspecialchars($d['partita_iva'] ?? '') ?>"
             placeholder="12345678901"
             maxlength="11" pattern="\d{11}"
             <?= $edit ? 'readonly style="opacity:0.6;"' : '' ?>/>
      <?php if ($edit): ?>
        <span style="font-size:0.7rem; color:var(--muted);">La P.IVA non può essere modificata.</span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="indirizzo">Indirizzo</label>
      <input type="text" id="indirizzo" name="indirizzo"
             value="<?= htmlspecialchars($d['indirizzo'] ?? '') ?>"
             placeholder="Via Roma 1, Milano"/>
    </div>

    <div class="form-group">
      <label for="telefono">Telefono</label>
      <input type="tel" id="telefono" name="telefono"
             value="<?= htmlspecialchars($d['telefono'] ?? '') ?>"
             placeholder="+39 02 1234567"/>
    </div>

    <div class="form-group">
      <label for="mail">Email</label>
      <input type="email" id="mail" name="mail"
             value="<?= htmlspecialchars($d['mail'] ?? '') ?>"
             placeholder="info@struttura.it"/>
    </div>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">
        <?= $edit ? 'Salva modifiche' : 'Crea struttura' ?>
      </button>
      <a href="<?= APP_URL ?>/strutture<?= $edit ? '/show/' . $struttura['id'] : '' ?>"
         class="btn btn--outline">Annulla</a>
    </div>

  </form>
</div>
