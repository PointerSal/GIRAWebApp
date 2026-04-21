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
    <a href="#cos-e-gira"   style="color:var(--green); font-size:0.82rem;">1. Cos'è GIRA</a>
    <a href="#ruoli"        style="color:var(--green); font-size:0.82rem;">2. Ruoli utente</a>
    <a href="#dashboard"    style="color:var(--green); font-size:0.82rem;">3. Dashboard</a>
    <a href="#alert"        style="color:var(--green); font-size:0.82rem;">4. Alert</a>
    <a href="#soglie"       style="color:var(--green); font-size:0.82rem;">5. Soglie e silenzio notturno</a>
    <a href="#dispositivi"  style="color:var(--green); font-size:0.82rem;">6. Dispositivi e sensori</a>
    <a href="#reparti"      style="color:var(--green); font-size:0.82rem;">7. Reparti</a>
    <a href="#utenti"       style="color:var(--green); font-size:0.82rem;">8. Gestione utenti</a>
    <a href="#strutture"    style="color:var(--green); font-size:0.82rem;">9. Strutture</a>
    <a href="#posizioni"    style="color:var(--green); font-size:0.82rem;">10. Posizioni rilevate</a>
    <a href="#notifiche"    style="color:var(--green); font-size:0.82rem;">11. Notifiche push</a>
    <a href="#report"       style="color:var(--green); font-size:0.82rem;">12. Report</a>
    <a href="#primo-accesso" style="color:var(--green); font-size:0.82rem;">13. Primo accesso</a>
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
    direttamente dalla dashboard o tramite notifiche push sul proprio dispositivo.
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
    ['Superadmin', 'pill--red',   'Accesso completo a tutte le strutture e funzionalità della piattaforma. Gestisce strutture, piani e configurazioni globali.'],
    ['Admin',      'pill--warn',  'Gestisce una o più strutture RSA. Può creare utenti, configurare dispositivi e reparti, visualizzare tutti gli alert della propria struttura e generare report.'],
    ['Medico',     'pill--ok',    'Visualizza alert, storico e report di tutte le strutture assegnate. Non può gestire o chiudere alert, né modificare configurazioni.'],
    ['Operatore',  'pill--muted', 'Monitora i dispositivi a lui assegnati. Riceve alert, può prenderli in carico e chiuderli.'],
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
    <li><strong>Superadmin</strong> — panoramica globale di tutte le strutture, contatori e alert urgenti.</li>
    <li><strong>Admin</strong> — contatori della struttura attiva, stato in tempo reale di tutti i dispositivi con icone letto colorate per stato.</li>
    <li><strong>Medico</strong> — alert aperti e storico recente delle strutture assegnate.</li>
    <li><strong>Operatore</strong> — stato dei soli dispositivi assegnati, con indicazione di alert attivi.</li>
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
    ['🆘 SOS',      'pill--red',   'Pulsante di emergenza premuto dal paziente. Priorità massima.'],
    ['🔴 Rosso',    'pill--red',   'Paziente in posizione a rischio da oltre ' . (defined('ALERT_ROSSO_MIN') ? ALERT_ROSSO_MIN : 30) . ' minuti. Intervento urgente.'],
    ['🟠 Arancio',  'pill--warn',  'Paziente in posizione a rischio da oltre ' . (defined('ALERT_ARANCIO_MIN') ? ALERT_ARANCIO_MIN : 20) . ' minuti. Attenzione richiesta.'],
    ['🔋 Batteria', 'pill--warn',  'Batteria del sensore sotto il ' . (defined('ALERT_BATT_SOGLIA') ? ALERT_BATT_SOGLIA : 20) . '%. Sostituire o ricaricare.'],
    ['📡 Offline',  'pill--muted', 'Il sensore non invia dati da oltre 10 minuti. Verificare connessione e posizionamento.'],
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
    <li><strong>Prendi in carico</strong> — segnala che stai gestendo l'alert. Il tuo nome appare sull'alert. Disponibile per admin e operatori.</li>
    <li><strong>Chiudi</strong> — chiude l'alert con nota opzionale (es. "Paziente riposizionato"). Disponibile solo per SOS, Batteria e Offline. Solo admin e operatori.</li>
    <li><strong>Storico</strong> — visualizza tutti gli alert chiusi con filtri per tipo e struttura. Disponibile per admin e medici.</li>
  </ul>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Il ruolo Medico può visualizzare gli alert ma non può prenderli in carico né chiuderli — questa responsabilità spetta agli operatori e agli admin.
  </p>

  <p class="section-label" style="margin-top:var(--space-xl);">Chiusura alert per tipo</p>
  <?php
  $chiusura = [
    ['🔴 Rosso',    'pill--red',   'Auto-chiusura', 'Automatica quando il paziente cambia posizione. Non può essere chiuso manualmente.'],
    ['🟠 Arancio',  'pill--warn',  'Auto-chiusura', 'Automatica quando il paziente cambia posizione. Non può essere chiuso manualmente.'],
    ['🆘 SOS',      'pill--red',   'Manuale',       'L\'operatore deve chiuderlo dopo aver raggiunto e assistito il paziente.'],
    ['🔋 Batteria', 'pill--warn',  'Manuale',       'Chiudere dopo aver sostituito o ricaricato il sensore.'],
    ['📡 Offline',  'pill--muted', 'Automatica',    'Si chiude automaticamente quando il sensore torna online. Può essere chiuso anche manualmente.'],
  ];
  foreach ($chiusura as [$nome, $pill, $modo, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm); align-items:flex-start;">
      <span class="pill <?= $pill ?>" style="width:80px; flex-shrink:0; font-size:0.65rem;"><?= $nome ?></span>
      <span style="font-size:0.72rem; width:90px; flex-shrink:0; padding-top:2px; color:var(--muted);"><?= $modo ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- 5. Soglie -->
<div class="card" id="soglie" style="margin-bottom:var(--space-xl);">
  <p class="section-label">5. Soglie e silenzio notturno</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-bottom:var(--space-md);">
    Le soglie definiscono dopo quanti minuti di immobilità vengono generati gli alert.
    Sono configurabili a due livelli:
  </p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg); margin-bottom:var(--space-md);">
    <li><strong>Superadmin</strong> — imposta i valori e i range min/max consentiti per ogni struttura.</li>
    <li><strong>Admin</strong> — può modificare le soglie della propria struttura entro i limiti definiti dal superadmin.</li>
  </ul>

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

<!-- 6. Dispositivi -->
<div class="card" id="dispositivi" style="margin-bottom:var(--space-xl);">
  <p class="section-label">6. Dispositivi e sensori</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Ogni sensore giroscopico viene registrato nel sistema con il suo indirizzo MAC.
    Una volta registrato, il sistema inizia automaticamente a ricevere e processare i dati.
  </p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7; margin-top:var(--space-md);">
    Dalla scheda dettaglio di ogni dispositivo è possibile vedere gli operatori assegnati
    e rimuovere singole associazioni. Un dispositivo non assegnato ad alcun operatore
    è evidenziato nella lista con un avviso — verificare che sia intenzionale.
  </p>

  <p class="section-label" style="margin-top:var(--space-xl);">Stati del sensore</p>
  <?php
  $stati = [
    ['OK',      'pill--ok',   'Il sensore sta inviando dati regolarmente.'],
    ['Offline', 'pill--muted','Il sensore non invia dati da oltre 10 minuti.'],
    ['Arancio', 'pill--warn', 'Alert di immobilità di attenzione attivo.'],
    ['Rosso',   'pill--red',  'Alert di immobilità urgente attivo.'],
    ['🆘 SOS',  'pill--red',  'Pulsante di emergenza premuto.'],
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
<div class="card" id="reparti" style="margin-bottom:var(--space-xl);">
  <p class="section-label">7. Reparti</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    I reparti definiscono la posizione fisica dei dispositivi all'interno della struttura.
    Sono organizzati su due livelli:
  </p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; margin-top:var(--space-sm); padding-left:var(--space-lg);">
    <li><strong>Area</strong> — es. "Piano Terra", "Primo Piano", "Ala Nord"</li>
    <li><strong>Subarea</strong> — es. "Stanza 1", "Letto A", "Camera 12"</li>
  </ul>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Un reparto non può essere eliminato se ha dispositivi attivi assegnati.
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
    <li>Gli <strong>operatori</strong> devono avere dispositivi assegnati per poter monitorare i pazienti.</li>
    <li>I <strong>medici</strong> vedono automaticamente tutti i dispositivi delle strutture a loro assegnate — non è necessaria un'assegnazione individuale.</li>
    <li>Un utente può essere <strong>disattivato</strong> dalla scheda modifica — non può più accedere ma i suoi dati vengono conservati.</li>
    <li>La <strong>password</strong> può essere reimpostata dall'admin in qualsiasi momento dalla scheda modifica utente.</li>
  </ul>
</div>

<!-- 9. Strutture -->
<div class="card" id="strutture" style="margin-bottom:var(--space-xl);">
  <p class="section-label">9. Strutture</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Ogni struttura RSA è un'entità indipendente con i propri dispositivi, utenti e reparti.
    Un utente può essere associato a più strutture contemporaneamente.
  </p>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Se sei associato a più strutture, il selettore in topbar ti permette di
    passare da una all'altra senza dover fare logout. Tutte le schermate —
    alert, dispositivi, report — si filtrano automaticamente sulla struttura selezionata.
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
    ['SCONOSCIUTO', '⚠️ A rischio', 'Posizione non classificabile (es. seduto). Monitorato come posizione a rischio per sicurezza.'],
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

<!-- 11. Notifiche push -->
<div class="card" id="notifiche" style="margin-bottom:var(--space-xl);">
  <p class="section-label">11. Notifiche push</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    GIRA può inviare notifiche push direttamente sul tuo telefono o browser, anche quando
    l'app non è aperta. Le notifiche vengono generate in tempo reale al momento dell'apertura
    di un nuovo alert.
  </p>

  <p class="section-label" style="margin-top:var(--space-xl);">Come attivarle</p>
  <ol style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg);">
    <li>Accedi a GIRA dal browser del dispositivo su cui vuoi ricevere le notifiche.</li>
    <li>Il browser mostrerà automaticamente una richiesta di permesso — seleziona <strong>Consenti</strong>.</li>
    <li>Vai su <strong>Il mio profilo</strong> (icona in alto a destra) e nella sezione <strong>Notifiche push</strong> scegli per quali tipi di alert vuoi essere notificato.</li>
    <li>Salva le preferenze.</li>
  </ol>
  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Le preferenze push sono personali e si applicano a tutte le strutture assegnate.
    Ogni dispositivo/browser su cui accedi genera una subscription indipendente —
    puoi ricevere notifiche su più dispositivi contemporaneamente.
  </p>

  <p class="section-label" style="margin-top:var(--space-xl);">Tipi di alert notificabili</p>
  <?php
  $notif = [
    ['🔴 Rosso',    'pill--red',   'Notifica immediata. Rimane visibile finché non viene toccata.'],
    ['🟠 Arancio',  'pill--warn',  'Notifica immediata.'],
    ['🔋 Batteria', 'pill--warn',  'Notifica immediata.'],
    ['📡 Offline',  'pill--muted', 'Notifica generata dal controllo automatico ogni 5 minuti.'],
    ['🆘 SOS',      'pill--red',   'Notifica immediata con priorità massima. Rimane visibile finché non viene toccata.'],
  ];
  foreach ($notif as [$nome, $pill, $desc]):
  ?>
    <div class="table-row" style="margin-bottom:var(--space-sm);">
      <span class="pill <?= $pill ?>" style="width:80px; flex-shrink:0; font-size:0.65rem;"><?= $nome ?></span>
      <span style="font-size:0.82rem; color:var(--text); line-height:1.6;"><?= $desc ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- 12. Report -->
<div class="card" id="report" style="margin-bottom:var(--space-xl);">
  <p class="section-label">12. Report</p>
  <p style="font-size:0.85rem; color:var(--text); line-height:1.7;">
    Il report permette di analizzare gli alert del periodo selezionato per ogni dispositivo.
    È accessibile da admin e medici.
  </p>

  <p class="section-label" style="margin-top:var(--space-xl);">Dati mostrati per dispositivo</p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg);">
    <li><strong>Alert rossi</strong> — numero di episodi e tempo totale in minuti/ore.</li>
    <li><strong>Posizione prevalente</strong> — la posizione più frequente al momento degli alert rossi.</li>
    <li><strong>Episodi offline</strong> — numero di disconnessioni e tempo totale offline.</li>
  </ul>

  <p class="section-label" style="margin-top:var(--space-xl);">Filtri disponibili</p>
  <ul style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg);">
    <li><strong>Dispositivo</strong> — uno o più dispositivi (Ctrl+click per selezione multipla), raggruppati per reparto.</li>
    <li><strong>Periodo</strong> — date libere o preset rapidi (ultimi 7 giorni, ultimi 30 giorni).</li>
    <li><strong>Tipo alert</strong> — includi o escludi rosso e/o offline.</li>
  </ul>

  <p style="font-size:0.85rem; color:var(--muted); margin-top:var(--space-md);">
    💡 Il pulsante <strong>Esporta CSV</strong> sarà disponibile con il piano Plus.
  </p>
</div>

<!-- 13. Primo accesso -->
<div class="card" id="primo-accesso" style="margin-bottom:var(--space-xl);">
  <p class="section-label">13. Primo accesso</p>
  <ol style="font-size:0.82rem; color:var(--text); line-height:1.9; padding-left:var(--space-lg);">
    <li>Accedi con le credenziali fornite dal tuo amministratore.</li>
    <li>Al primo accesso ti verrà chiesto di impostare una nuova password personale.</li>
    <li>Dopo il cambio password verrai reindirizzato alla dashboard.</li>
    <li>Vai su <strong>Il mio profilo</strong> e configura le notifiche push per ricevere gli alert sul tuo dispositivo.</li>
    <li>Se sei un operatore, verifica di avere dispositivi assegnati — contatta il tuo admin in caso contrario.</li>
    <li>In caso di problemi di accesso, contatta il tuo amministratore per il reset della password.</li>
  </ol>
</div>

<?php include VIEW_PATH . 'layout/footer.php'; ?>
