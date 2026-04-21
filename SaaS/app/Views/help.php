<?php
// ============================================================
//  GIRA · app/Views/help.php
//  Manuale utente — unico per tutti i ruoli
// ============================================================
$page_title   = 'Guida — GIRA';
$current_page = 'help';
include VIEW_PATH . 'layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Guida all'uso</h1>
    <div class="page-header-sub">GIRA — Care Monitor RSA</div>
  </div>
</div>

<!-- Indice -->
<div class="card" style="margin-bottom:var(--space-xl);">
  <p class="section-label">Indice</p>
  <div style="display:flex; flex-direction:column; gap:6px;">
    <a href="#cos-e-gira" style="color:var(--green); font-size:0.82rem;">1. Cos'è GIRA</a>
    <a href="#ruoli" style="color:var(--green); font-size:0.82rem;">2. Ruoli utente</a>
    <a href="#dashboard" style="color:var(--green); font-size:0.82rem;">3. Dashboard</a>
    <a href="#alert" style="color:var(--green); font-size:0.82rem;">4. Alert</a>
    <a href="#soglie" style="color:var(--green); font-size:0.82rem;">5. Soglie e silenzio notturno</a>
    <a href="#device" style="color:var(--green); font-size:0.82rem;">6. Device e sensori</a>
    <a href="#ubicazioni" style="color:var(--green); font-size:0.82rem;">7. Reparti</a>
    <a href="#utenti" style="color:var(--green); font-size:0.82rem;">8. Gestione utenti</a>
    <a href="#strutture" style="color:var(--green); font-size:0.82rem;">9. Strutture</a>
    <a href="#posizioni" style="color:var(--green); font-size:0.82rem;">10. Posizioni rilevate</a>
    <a href="#primo-accesso" style="color:var(--green); font-size:0.82rem;">11. Primo accesso</a>
  </div>
</div>

<!-- 1. Cos'è GIRA -->
<div class="card" id="cos-e-gira" style="margin-bottom:var(--space-xl);">
  <p class="section-label">1. Cos'è GIRA</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    GIRA è una piattaforma di monitoraggio della postura per pazienti in strutture RSA.
    Tramite sensori giroscopici applicati ai pazienti, il sistema rileva continuamente
    la posizione corporea e genera alert automatici quando un paziente rimane in una
    posizione a rischio per troppo tempo, contribuendo alla prevenzione delle piaghe da decubito.
  </p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-top:var(--space-md);">
    Il sistema funziona 24 ore su 24, 7 giorni su 7, senza necessità di intervento manuale
    per il monitoraggio. Gli operatori ricevono alert in tempo reale e possono gestirli
    direttamente dalla dashboard.
  </p>
</div>

<!-- 2. Ruoli -->
<div class="card" id="ruoli" style="margin-bottom:var(--space-xl);">
  <p class="section-label">2. Ruoli utente</p>
  <p style="font-size:0.85rem; color:var(--muted); margin-bottom:var(--space-md);">
    GIRA prevede quattro ruoli con permessi differenti.
  </p>

  <?php
  $ruoli = [
    ['Superadmin', 'pill--red',  'Accesso completo a tutte le strutture e funzionalità della piattaforma. Gestisce strutture, piani e configurazioni globali.'],
    ['Admin',      'pill--warn', 'Gestisce una o più strutture RSA. Può creare utenti, configurare device e ubicazioni, visualizzare tutti gli alert della propria struttura.'],
    ['Medico',     'pill--ok',   'Visualizza alert e storico posizioni di tutte le strutture assegnate. Non può modificare configurazioni.'],
    ['Operatore',  'pill--muted', 'Monitora i device a lui assegnati. Riceve alert, può prenderli in carico e chiuderli.'],
  ];
  foreach ($ruoli as [$nome, $pill, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm);">
      <span class="pill <?= $pill ?>" style="width:80px; flex-shrink:0;"><?= $nome ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- 3. Dashboard -->
<div class="card" id="dashboard" style="margin-bottom:var(--space-xl);">
  <p class="section-label">3. Dashboard</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    La dashboard è la schermata principale di GIRA e si aggiorna automaticamente ogni
    <?= defined('POLLING_INTERVAL') ? POLLING_INTERVAL / 1000 : 10 ?> secondi senza ricaricare la pagina.
  </p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-top:var(--space-md);">
    A seconda del ruolo mostra contenuti diversi:
  </p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; margin-top:var(--space-sm); padding-left:var(--space-lg);">
    <li><strong>Superadmin</strong> — panoramica globale di tutte le strutture, contatori e alert urgenti</li>
    <li><strong>Admin</strong> — contatori della struttura attiva, stato in tempo reale di tutti i device</li>
    <li><strong>Medico</strong> — alert aperti e storico posizioni recente delle strutture assegnate</li>
    <li><strong>Operatore</strong> — stato dei soli device assegnati, con indicazione di alert attivi</li>
  </ul>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Se sei associato a più strutture, usa il selettore in alto a destra per filtrare la visualizzazione.
  </p>
</div>

<!-- 4. Alert -->
<div class="card" id="alert" style="margin-bottom:var(--space-xl);">
  <p class="section-label">4. Alert</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-bottom:var(--space-md);">
    Gli alert vengono generati automaticamente dal sistema in base alla posizione rilevata.
  </p>

  <?php
  $tipi_alert = [
    ['🆘 SOS',        'pill--red',  'Pulsante di emergenza premuto dal paziente. Priorità massima.'],
    ['🔴 Rosso',      'pill--red',  'Paziente in posizione a rischio da oltre ' . (defined('ALERT_ROSSO_MIN') ? ALERT_ROSSO_MIN : 30) . ' minuti. Intervento urgente.'],
    ['🟠 Arancio',    'pill--warn', 'Paziente in posizione a rischio da oltre ' . (defined('ALERT_ARANCIO_MIN') ? ALERT_ARANCIO_MIN : 15) . ' minuti. Attenzione richiesta.'],
    ['🔋 Batteria',   'pill--warn', 'Batteria del sensore sotto il ' . (defined('ALERT_BATT_SOGLIA') ? ALERT_BATT_SOGLIA : 20) . '%. Sostituire o ricaricare.'],
    ['📡 Offline',    'pill--muted', 'Il sensore non invia dati da oltre 10 minuti. Verificare connessione.'],
  ];
  foreach ($tipi_alert as [$nome, $pill, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm);">
      <span class="pill <?= $pill ?>" style="width:80px; flex-shrink:0; font-size:0.65rem;"><?= $nome ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>

  <p class="section-label" style="margin-top:var(--space-xl);">Gestione alert</p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg);">
    <li><strong>Prendi in carico</strong> — segnala che stai gestendo l'alert. Il tuo nome appare sull'alert. Disponibile per tutti i tipi.</li>
    <li><strong>Chiudi</strong> — chiude l'alert con nota opzionale (es. "Paziente riposizionato"). Disponibile solo per SOS, Batteria e Offline.</li>
    <li><strong>Storico</strong> — visualizza tutti gli alert chiusi con filtri per tipo e struttura.</li>
  </ul>

  <p class="section-label" style="margin-top:var(--space-xl);">Chiusura alert per tipo</p>
  <?php
  $chiusura = [
    ['🔴 Rosso',      'pill--red',  'Auto-chiusura', 'Automatica quando il paziente cambia posizione. Non può essere chiuso manualmente.'],
    ['🟠 Arancio',    'pill--warn', 'Auto-chiusura', 'Automatica quando il paziente cambia posizione. Non può essere chiuso manualmente.'],
    ['🆘 SOS',        'pill--red',  'Manuale',       'L\'operatore deve chiuderlo dopo aver raggiunto e assistito il paziente.'],
    ['🔋 Batteria',   'pill--warn', 'Manuale',       'Chiudere dopo aver sostituito o ricaricato il sensore.'],
    ['📡 Offline',    'pill--muted', 'Automatica',    'Si chiude automaticamente quando il sensore torna online. Può essere chiuso anche manualmente.'],
  ];
  foreach ($chiusura as [$nome, $pill, $modo, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm); align-items:flex-start;">
      <span class="pill <?= $pill ?>" style="width:80px; flex-shrink:0; font-size:0.65rem;"><?= $nome ?></span>
      <span style="font-size:0.72rem; color:var(--muted); width:90px; flex-shrink:0; padding-top:2px;"><?= $modo ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>

  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 La chiusura automatica degli alert Rosso e Arancio garantisce che l'alert rimanga
    aperto finché il paziente non è stato effettivamente riposizionato — evitando che
    venga chiuso per errore prima dell'intervento.
  </p>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-sm);">
    🌙 Durante le ore di silenzio notturno gli alert di immobilità non vengono generati.
    Gli alert SOS e Batteria rimangono sempre attivi.
  </p>
</div>

<!-- 5. Soglie -->
<div class="card" id="soglie" style="margin-bottom:var(--space-xl);">
  <p class="section-label">5. Soglie e silenzio notturno</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-bottom:var(--space-md);">
    GIRA permette di configurare le soglie di immobilità e le ore di silenzio notturno
    per ogni struttura. La gestione è a due livelli:
  </p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg); margin-bottom:var(--space-md);">
    <li>
      <strong>Superadmin</strong> — imposta i valori delle soglie e definisce il range
      entro cui gli admin possono modificarle. Configura anche i limiti del silenzio notturno.
    </li>
    <li>
      <strong>Admin</strong> — può modificare le soglie e le ore di silenzio entro i limiti
      definiti dal superadmin. Accede alla pagina tramite "Soglie" nel menu laterale.
    </li>
  </ul>

  <p class="section-label" style="margin-top:var(--space-lg);">Soglie immobilità</p>
  <div class="table-row" style="margin-bottom:var(--space-sm);">
    <span class="pill pill--warn" style="width:80px; flex-shrink:0; font-size:0.65rem;">🟠 Arancio</span>
    <span style="font-size:0.82rem; color:var(--text); line-height:1.6;">
      Alert di attenzione — il paziente è in posizione a rischio da troppo tempo.
      Soglia di default: <?= defined('ALERT_ARANCIO_MIN') ? ALERT_ARANCIO_MIN : 20 ?> minuti.
    </span>
  </div>
  <div class="table-row" style="margin-bottom:var(--space-sm);">
    <span class="pill pill--red" style="width:80px; flex-shrink:0; font-size:0.65rem;">🔴 Rosso</span>
    <span style="font-size:0.82rem; color:var(--text); line-height:1.6;">
      Alert urgente — il paziente è in posizione a rischio da molto tempo. Intervento immediato.
      Soglia di default: <?= defined('ALERT_ROSSO_MIN') ? ALERT_ROSSO_MIN : 30 ?> minuti.
      La soglia rossa deve essere almeno 5 minuti maggiore della soglia arancio.
    </span>
  </div>

  <p class="section-label" style="margin-top:var(--space-lg);">Silenzio notturno</p>
  <p style="font-size:0.82rem; color:var(--text); line-height:1.7;">
    Durante le ore notturne è normale che un paziente dorma supino a lungo — generare
    alert ogni 20-30 minuti tutta la notte sarebbe inutile e stressante per il personale.
    Il silenzio notturno sospende automaticamente gli alert di immobilità nelle ore configurate.
  </p>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-sm);">
    💡 Gli alert SOS (pulsante di emergenza) e Batteria rimangono sempre attivi,
    anche durante il silenzio notturno.
  </p>
</div>

<!-- 6. Device -->
<div class="card" id="device" style="margin-bottom:var(--space-xl);">
  <p class="section-label">6. Device e sensori</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Ogni sensore giroscopico viene registrato nel sistema con il suo indirizzo MAC.
    Una volta registrato, il sistema inizia automaticamente a ricevere e processare i dati.
  </p>
  <p class="section-label" style="margin-top:var(--space-xl);">Stati del sensore</p>
  <?php
  $stati = [
    ['OK',       'pill--ok',   'Il sensore sta inviando dati regolarmente.'],
    ['Offline',  'pill--muted', 'Il sensore non invia dati da oltre 10 minuti.'],
    ['Arancio',  'pill--warn', 'Alert di immobilità di attenzione attivo.'],
    ['Rosso',    'pill--red',  'Alert di immobilità urgente attivo.'],
    ['🆘 SOS',   'pill--red',  'Pulsante di emergenza premuto.'],
  ];
  foreach ($stati as [$nome, $pill, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm);">
      <span class="pill <?= $pill ?>" style="width:80px; flex-shrink:0; font-size:0.65rem;"><?= $nome ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- 7. Reparti -->
<div class="card" id="ubicazioni" style="margin-bottom:var(--space-xl);">
  <p class="section-label">7. Reparti</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Le ubicazioni definiscono la posizione fisica dei device all'interno della struttura.
    Sono organizzate su due livelli:
  </p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; margin-top:var(--space-sm); padding-left:var(--space-lg);">
    <li><strong>Area</strong> — es. "Piano Terra", "Primo Piano", "Ala Nord"</li>
    <li><strong>Subarea</strong> — es. "Stanza 1", "Letto A", "Camera 12"</li>
  </ul>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Un'ubicazione non può essere eliminata se ha device attivi assegnati.
  </p>
</div>

<!-- 8. Utenti -->
<div class="card" id="utenti" style="margin-bottom:var(--space-xl);">
  <p class="section-label">8. Gestione utenti</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Gli utenti vengono creati dall'admin o dal superadmin. Non è prevista registrazione pubblica.
  </p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; margin-top:var(--space-sm); padding-left:var(--space-lg);">
    <li>Al momento della creazione viene assegnata una <strong>password temporanea</strong>.</li>
    <li>Al primo accesso l'utente è <strong>obbligato a cambiarla</strong>.</li>
    <li>Gli <strong>operatori</strong> devono avere device assegnati per poter monitorare i pazienti.</li>
    <li>Un utente può essere <strong>disattivato</strong> dalla scheda modifica — non può più accedere ma i suoi dati vengono conservati.</li>
    <li>La <strong>password</strong> può essere reimpostata dall'admin in qualsiasi momento.</li>
  </ul>
</div>

<!-- 9. Strutture -->
<div class="card" id="strutture" style="margin-bottom:var(--space-xl);">
  <p class="section-label">9. Strutture</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Ogni struttura RSA è un'entità indipendente con i propri device, utenti e ubicazioni.
    Un utente può essere associato a più strutture contemporaneamente.
  </p>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Se sei associato a più strutture, il selettore in topbar ti permette di
    passare da una all'altra senza dover fare logout.
  </p>
</div>

<!-- 10. Posizioni -->
<div class="card" id="posizioni" style="margin-bottom:var(--space-xl);">
  <p class="section-label">10. Posizioni rilevate</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-bottom:var(--space-md);">
    Il sensore rileva continuamente l'orientamento del paziente e classifica la posizione
    in base ai dati dell'accelerometro.
  </p>
  <?php
  $posizioni = [
    ['SUPINO',      '⚠️ A rischio', 'Il paziente è sdraiato con il petto verso l\'alto.'],
    ['PRONO',       '⚠️ A rischio', 'Il paziente è sdraiato con il petto verso il basso.'],
    ['LATO_A',      '✅ Sicuro',    'Il paziente è sdraiato su un fianco.'],
    ['LATO_B',      '✅ Sicuro',    'Il paziente è sdraiato sull\'altro fianco.'],
    ['SCONOSCIUTO', '⚠️ A rischio', 'Posizione non classificabile (es. seduto con schienale alto). Monitorato come posizione a rischio per sicurezza.'],
  ];
  foreach ($posizioni as [$pos, $stato, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm); align-items:flex-start;">
      <span style="font-family:var(--font-mono); font-size:0.72rem; color:var(--green); width:100px; flex-shrink:0; padding-top:2px;"><?= $pos ?></span>
      <span style="font-size:0.72rem; width:90px; flex-shrink:0; padding-top:2px; color:<?= str_contains($stato, 'rischio') ? 'var(--amber)' : 'var(--green)' ?>"><?= $stato ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Un cambio di posizione viene confermato solo dopo <?= defined('MIN_POSIZIONE_MINUTI') ? MIN_POSIZIONE_MINUTI : 3 ?> minuti
    di stabilità — questo evita falsi rilevamenti causati da movimenti momentanei o rumore del sensore.
  </p>
</div>

<!-- 11. Primo accesso -->
<div class="card" id="primo-accesso" style="margin-bottom:var(--space-xl);">
  <p class="section-label">11. Primo accesso</p>
  <ol style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg);">
    <li>Accedi con le credenziali fornite dal tuo amministratore.</li>
    <li>Al primo accesso ti verrà chiesto di impostare una nuova password personale.</li>
    <li>Dopo il cambio password verrai reindirizzato alla dashboard.</li>
    <li>Se sei un operatore, verifica di avere device assegnati — contatta il tuo admin in caso contrario.</li>
    <li>In caso di problemi di accesso, contatta il tuo amministratore per il reset della password.</li>
  </ol>
</div>

<?php include VIEW_PATH . 'layout/footer.php'; ?>