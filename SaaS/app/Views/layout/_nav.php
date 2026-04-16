<?php
// ============================================================
//  GIRA · app/Views/layout/_nav.php
//  Navigazione condivisa tra sidebar desktop e drawer mobile
//  Incluso da header.php
// ============================================================
$cp = $current_page ?? '';
?>

<?php if (Auth::isSuperadmin()): ?>

  <div class="nav-sezione">Piattaforma</div>
  <a href="<?= APP_URL ?>/dashboard" class="<?= $cp === 'dashboard'  ? 'attivo' : '' ?>">▦ Dashboard</a>
  <a href="<?= APP_URL ?>/strutture" class="<?= $cp === 'strutture'  ? 'attivo' : '' ?>">🏥 Strutture</a>
  <a href="<?= APP_URL ?>/utenti" class="<?= $cp === 'utenti'     ? 'attivo' : '' ?>">👥 Utenti</a>
  <a href="<?= APP_URL ?>/device" class="<?= $cp === 'device'     ? 'attivo' : '' ?>">📡 Device</a>

<?php elseif (Auth::isAdmin()): ?>

  <div class="nav-sezione">Monitoraggio</div>
  <a href="<?= APP_URL ?>/dashboard" class="<?= $cp === 'dashboard'  ? 'attivo' : '' ?>">▦ Dashboard</a>
  <a href="<?= APP_URL ?>/alert" class="<?= $cp === 'alert'      ? 'attivo' : '' ?>">🔔 Alert</a>

  <div class="nav-sezione">Gestione</div>
  <a href="<?= APP_URL ?>/device" class="<?= $cp === 'device'     ? 'attivo' : '' ?>">📡 Device</a>
  <a href="<?= APP_URL ?>/ubicazioni" class="<?= $cp === 'ubicazioni' ? 'attivo' : '' ?>">📍 Ubicazioni</a>
  <a href="<?= APP_URL ?>/utenti" class="<?= $cp === 'utenti'     ? 'attivo' : '' ?>">👥 Utenti</a>
  <a href="<?= APP_URL ?>/soglie" class="<?= $cp === 'soglie' ? 'attivo' : '' ?>">⚙ Soglie</a>
  <a href="<?= APP_URL ?>/report" class="<?= $cp === 'report'     ? 'attivo' : '' ?>">📊 Report</a>

<?php elseif (Auth::isMedico()): ?>

  <div class="nav-sezione">Monitoraggio</div>
  <a href="<?= APP_URL ?>/dashboard" class="<?= $cp === 'dashboard'  ? 'attivo' : '' ?>">▦ Dashboard</a>
  <a href="<?= APP_URL ?>/alert" class="<?= $cp === 'alert'      ? 'attivo' : '' ?>">🔔 Alert</a>
  <a href="<?= APP_URL ?>/alert/storico" class="<?= $cp === 'alert-storico' ? 'attivo' : '' ?>">📋 Storico alert</a>
  <a href="<?= APP_URL ?>/report" class="<?= $cp === 'report'     ? 'attivo' : '' ?>">📊 Report</a>

<?php else: /* RUOLO_UTENTE — operatore */ ?>

  <div class="nav-sezione">Monitoraggio</div>
  <a href="<?= APP_URL ?>/dashboard" class="<?= $cp === 'dashboard'  ? 'attivo' : '' ?>">▦ Dashboard</a>
  <a href="<?= APP_URL ?>/alert" class="<?= $cp === 'alert'      ? 'attivo' : '' ?>">🔔 Alert</a>

<?php endif; ?>

<!-- Guida — visibile per tutti i ruoli -->
<div class="nav-sezione" style="margin-top:auto;"></div>
<a href="<?= APP_URL ?>/help" class="<?= $cp === 'help' ? 'attivo' : '' ?>">❓ Guida</a>