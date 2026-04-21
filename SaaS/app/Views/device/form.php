<?php
// ============================================================
//  GIRA · app/Views/device/form.php
// ============================================================
$edit = isset($device);
$d    = $form_data;
?>

<div class="page-header">
  <div>
    <h1><?= $edit ? 'Modifica device' : 'Nuovo device' ?></h1>
    <div class="page-header-sub">
      <?= $edit ? htmlspecialchars($device['label'] ?? $device['mac']) : 'Registra un nuovo sensore giroscopico' ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/device<?= $edit ? '/show/' . $device['id'] : '' ?>"
    class="btn btn--outline">← Annulla</a>
</div>

<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px;">
  <form action="<?= APP_URL ?>/device/<?= $edit ? 'modifica-post' : 'crea-post' ?>" method="POST">

    <?php if ($edit): ?>
      <input type="hidden" name="id" value="<?= $device['id'] ?>" />
    <?php endif; ?>

    <!-- Struttura -->
    <div class="form-group">
      <label for="id_struttura">Struttura RSA *</label>
      <?php if ($edit): ?>
        <input type="hidden" name="id_struttura" value="<?= $device['id_struttura'] ?>" />
        <?php
        // Trova nome struttura
        $stmt = Database::getInstance()->prepare('SELECT ragione_sociale FROM gir_struttura WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $device['id_struttura']]);
        $nome_struttura = $stmt->fetchColumn();
        ?>
        <input type="text" value="<?= htmlspecialchars($nome_struttura) ?>"
          readonly style="opacity:0.6;" />
        <span style="font-size:0.7rem; color:var(--muted);">La struttura non può essere modificata.</span>
      <?php else: ?>
        <select id="id_struttura" name="id_struttura" required
          onchange="aggiornaUbicazioni(this.value)">
          <option value="">— Seleziona struttura —</option>
          <?php foreach ($strutture as $s): ?>
            <option value="<?= $s['id'] ?>"
              <?= (int)($d['id_struttura'] ?? $struttura_sel ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['ragione_sociale']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>

    <!-- MAC address -->
    <div class="form-group">
      <label for="mac">MAC address *</label>
      <?php if ($edit): ?>
        <input type="text" value="<?= htmlspecialchars($device['mac']) ?>"
          readonly style="opacity:0.6; font-family:var(--font-mono);" />
        <span style="font-size:0.7rem; color:var(--muted);">Il MAC non può essere modificato.</span>
      <?php else: ?>
        <input type="text" id="mac" name="mac"
          value="<?= htmlspecialchars($d['mac'] ?? '') ?>"
          placeholder="D1A3CD5B58CE"
          maxlength="17"
          style="font-family:var(--font-mono);"
          required />
        <span style="font-size:0.7rem; color:var(--muted);">
          12 caratteri hex, con o senza separatori (es: D1A3CD5B58CE o D1:A3:CD:5B:58:CE)
        </span>
      <?php endif; ?>
    </div>

    <!-- Label -->
    <div class="form-group">
      <label for="label">Etichetta / Nome paziente</label>
      <input type="text" id="label" name="label"
        value="<?= htmlspecialchars($d['label'] ?? '') ?>"
        placeholder="Es: Sig. Rossi — Stanza 3" />
      <span style="font-size:0.7rem; color:var(--muted);">
        Nome leggibile che apparirà nella dashboard
      </span>
    </div>

    <!-- Reparto -->
    <div class="form-group">
      <label for="id_ubicazione">Reparto</label>
      <select id="id_ubicazione" name="id_ubicazione">
        <option value="">— Non assegnata —</option>
        <?php foreach ($ubicazioni as $u): ?>
          <option value="<?= $u['id'] ?>"
            <?= (int)($d['id_ubicazione'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['area'] . ($u['subarea'] ? ' · ' . $u['subarea'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if ($edit): ?>
      <!-- Stato attivo -->
      <div class="form-group">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
          <input type="checkbox" name="attivo" value="1"
            <?= $device['attivo'] ? 'checked' : '' ?>
            style="width:16px; height:16px;" />
          Device attivo
        </label>
      </div>
    <?php endif; ?>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">
        <?= $edit ? 'Salva modifiche' : 'Registra device' ?>
      </button>
      <a href="<?= APP_URL ?>/device<?= $edit ? '/show/' . $device['id'] : '' ?>"
        class="btn btn--outline">Annulla</a>
    </div>

  </form>
</div>

<?php if (!$edit): ?>
  <script>
    // Aggiorna dinamicamente le ubicazioni quando cambia la struttura
    function aggiornaUbicazioni(idStruttura) {
      const sel = document.getElementById('id_ubicazione');
      sel.innerHTML = '<option value="">— Caricamento... —</option>';

      if (!idStruttura) {
        sel.innerHTML = '<option value="">— Non assegnata —</option>';
        return;
      }

      fetch('<?= APP_URL ?>/device/ubicazioni-json?id_struttura=' + idStruttura)
        .then(r => r.json())
        .then(data => {
          sel.innerHTML = '<option value="">— Non assegnata —</option>';
          data.forEach(u => {
            const label = u.area + (u.subarea ? ' · ' + u.subarea : '');
            sel.innerHTML += `<option value="${u.id}">${label}</option>`;
          });
        })
        .catch(() => {
          sel.innerHTML = '<option value="">— Non assegnata —</option>';
        });
    }
  </script>
<?php endif; ?>