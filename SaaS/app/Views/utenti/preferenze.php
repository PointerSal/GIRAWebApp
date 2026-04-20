<?php
// ============================================================
//  GIRA · app/Views/utenti/preferenze.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1>Preferenze notifiche</h1>
    <div class="page-header-sub">
      <?= htmlspecialchars($target['nome'] . ' ' . $target['cognome']) ?>
    </div>
  </div>
  <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">← Utenti</a>
</div>

<?php if (!empty($successo)): ?>
  <div class="alert-flash alert-flash--ok"><?= htmlspecialchars($successo) ?></div>
<?php endif; ?>
<?php if (!empty($errore)): ?>
  <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="card" style="max-width:500px;">
  <form action="<?= APP_URL ?>/utenti/preferenze-post" method="POST">
    <input type="hidden" name="id" value="<?= $target['id'] ?>" />

    <!-- Canali notifica -->
    <p class="section-label">Canali di notifica</p>

    <?php
    $checks = [
      'push_attiva'     => ['Push notification', 'Notifica sul telefono/browser (consigliato per operatori)'],
      'mail_attiva'     => ['Email', 'Abilita le notifiche via email'],
      'mail_istantanea' => ['Email istantanea', 'Ricevi una email ad ogni alert'],
      'mail_riepilogo'  => ['Email riepilogo', 'Ricevi un riepilogo giornaliero'],
    ];
    foreach ($checks as $name => [$label, $desc]):
    ?>
      <div class="form-group" style="margin-bottom:var(--space-sm);">
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; text-transform:none; letter-spacing:0; font-size:0.85rem; color:var(--text);">
          <input type="checkbox" name="<?= $name ?>" value="1"
            <?= !empty($pref[$name]) ? 'checked' : '' ?>
            style="width:16px; height:16px; margin-top:2px; flex-shrink:0;" />
          <span>
            <?= $label ?>
            <span style="display:block; font-size:0.72rem; color:var(--muted); margin-top:2px;"><?= $desc ?></span>
          </span>
        </label>
      </div>
    <?php endforeach; ?>

    <!-- Ora riepilogo -->
    <div class="form-group" style="margin-top:var(--space-md);">
      <label for="ora_riepilogo">Ora invio riepilogo</label>
      <select id="ora_riepilogo" name="ora_riepilogo">
        <?php for ($h = 0; $h < 24; $h++): ?>
          <option value="<?= $h ?>" <?= (int)($pref['ora_riepilogo'] ?? 7) === $h ? 'selected' : '' ?>>
            <?= sprintf('%02d:00', $h) ?>
          </option>
        <?php endfor; ?>
      </select>
    </div>

    <!-- Tipi di alert -->
    <p class="section-label" style="margin-top:var(--space-xl);">Tipi di alert ricevuti</p>

    <?php
    $alert_checks = [
      'alert_rosso'    => ['🔴 Alert rosso',    'Immobilità oltre soglia critica'],
      'alert_arancio'  => ['🟠 Alert arancio',  'Immobilità oltre soglia di attenzione'],
      'alert_batteria' => ['🔋 Batteria scarica', 'Batteria del sensore sotto il ' . ALERT_BATT_SOGLIA . '%'],
      'alert_offline'  => ['📡 Device offline', 'Sensore non risponde da troppo tempo'],
    ];
    foreach ($alert_checks as $name => [$label, $desc]):
    ?>
      <div class="form-group" style="margin-bottom:var(--space-sm);">
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; text-transform:none; letter-spacing:0; font-size:0.85rem; color:var(--text);">
          <input type="checkbox" name="<?= $name ?>" value="1"
            <?= !empty($pref[$name]) ? 'checked' : '' ?>
            style="width:16px; height:16px; margin-top:2px; flex-shrink:0;" />
          <span>
            <?= $label ?>
            <span style="display:block; font-size:0.72rem; color:var(--muted); margin-top:2px;"><?= $desc ?></span>
          </span>
        </label>
      </div>
    <?php endforeach; ?>

    <div class="flex-center gap-md mt-xl">
      <button type="submit" class="btn btn--primary">Salva preferenze</button>
      <a href="<?= APP_URL ?>/utenti" class="btn btn--outline">Annulla</a>
    </div>

  </form>
  <script>
    // Gestione toggle push notification
    (function() {
      const checkbox = document.querySelector('input[name="push_attiva"]');
      if (!checkbox) return;

      checkbox.addEventListener('change', async function() {
        if (!window.GiraPush) return;
        if (this.checked) {
          await window.GiraPush.attiva();
        } else {
          await window.GiraPush.disattiva();
        }
      });

      // Aggiorna lo stato visivo del checkbox in base alla subscription attiva
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
          if (window.GiraPush) {
            checkbox.checked = window.GiraPush.isAttiva();
          }
        }, 1000); // attende inizializzazione push.js
      });
    })();
  </script>
</div>