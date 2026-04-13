<?php
// ============================================================
//  GIRA · app/Views/ubicazioni/form.php
// ============================================================
$edit = isset($ubicazione);
$d    = $form_data;
?>

<div class="page-header">
  <div>
    <h1><?= $edit ? 'Modifica ubicazione' : 'Nuova ubicazione' ?></h1>
    <div class="page-header-sub"><?= htmlspecialchars($struttura['ragione_sociale']) ?></div>
  </div>
  <a href="<?= APP_URL ?>/ubicazioni?id_struttura=<?= $id_struttura ?>"
     class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:500px;">
  <form action="<?= APP_URL ?>/ubicazioni/<?= $edit ? 'modifica-post' : 'crea-post' ?>" method="POST">

    <input type="hidden" name="id_struttura" value="<?= $id_struttura ?>"/>
    <?php if ($edit): ?>
      <input type="hidden" name="id" value="<?= $ubicazione['id'] ?>"/>
    <?php endif; ?>

    <div class="form-group">
      <label for="area">Area *</label>
      <input type="text" id="area" name="area"
             value="<?= htmlspecialchars($d['area'] ?? '') ?>"
             placeholder="Es: Piano 1, Reparto Nord, Ala Est"
             required autofocus/>
      <span style="font-size:0.7rem; color:var(--muted);">
        Raggruppamento principale — es. piano o reparto
      </span>
    </div>

    <div class="form-group">
      <label for="subarea">Subarea</label>
      <input type="text" id="subarea" name="subarea"
             value="<?= htmlspecialchars($d['subarea'] ?? '') ?>"
             placeholder="Es: Stanza 8, Letto A"/>
      <span style="font-size:0.7rem; color:var(--muted);">
        Dettaglio opzionale — es. stanza o letto specifico
      </span>
    </div>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">
        <?= $edit ? 'Salva modifiche' : 'Aggiungi ubicazione' ?>
      </button>
      <a href="<?= APP_URL ?>/ubicazioni?id_struttura=<?= $id_struttura ?>"
         class="btn btn--outline">Annulla</a>
    </div>

  </form>
</div>
