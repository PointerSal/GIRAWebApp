<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GIRA — Care Monitor</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/gira.css"/>
  <link rel="manifest" href="/manifest.json"> 
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').then(function(registration) {
          console.log('ServiceWorker registrato con successo nell scope: ', registration.scope);
        }, function(err) {
          console.log('Registrazione ServiceWorker fallita: ', err);
        });
      });
    }
  </script>  
  <style>
    /* ── stili specifici di questa pagina ── */
    .btn-accedi {
      font-size: 0.7rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--green);
      border: 1px solid var(--green);
      padding: 6px 16px;
      border-radius: var(--radius-sm);
      text-decoration: none;
      transition: background var(--transition), color var(--transition);
    }
    .btn-accedi:hover {
      background: var(--green);
      color: var(--bg);
    }
    .hero { padding: var(--space-3xl) 0 var(--space-2xl); }
    .subtitle { max-width: 520px; margin-bottom: var(--space-2xl); }
    .status-card { display: inline-flex; align-items: center; gap: 20px; }
    .feature-icon { font-size: 1.4rem; margin-bottom: var(--space-md); display: block; }
    .feature-title { font-size: 0.78rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text); margin-bottom: var(--space-sm); font-weight: 500; }
    .alert-section { padding: 0 0 var(--space-2xl); }
    .table-row__pill { flex-shrink: 0; width: 72px; }
  </style>
</head>
<body>

<div class="glow-orb"></div>

<div class="wrapper">

  <!-- HEADER -->
  <header class="anim-fadein anim-delay-1">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 2a8 8 0 0 1 8 8 8 8 0 0 1-8 8 8 8 0 0 1-8-8 8 8 0 0 1 8-8zm0 3a5 5 0 1 0 0 10A5 5 0 0 0 12 7zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
      </svg>
    </div>
    <span class="logo-text">GI<span>RA</span></span>
    <a href="<?= APP_URL ?>/auth/login" class="btn-accedi" style="margin-left:auto;">Accedi</a>
    <span class="badge">Beta</span>
  </header>

  <!-- HERO -->
  <section class="hero anim-fadein anim-delay-2">
    <p class="eyebrow">Care Monitor · RSA</p>
    <h1>Monitoraggio<br/><em>intelligente</em><br/>del paziente.</h1>
    <p class="subtitle">
      GIRA (Guida intelligente al Riposizionamento Assistito) è una piattaforma, basata su sensori giroscopici, per il monitoraggio continuo della postura degli anziani. Alert in tempo reale per gli operatori sanitari, zero infrastruttura aggiuntiva.
    </p>
    <div class="card card--accent-left card--warn status-card anim-fadein anim-delay-3">
      <div class="status-dot status-dot--warn"></div>
      <div>
        <p style="color:var(--amber); font-size:0.78rem; font-weight:500; margin-bottom:2px;">Versione Beta</p>
        <p style="font-size:0.78rem;">Accesso riservato ai partner RSA · Disponibile a breve</p>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="section anim-fadein anim-delay-3">
    <p class="section-label">Funzionalità</p>
    <div class="grid grid--3">
      <div class="grid__item">
        <span class="feature-icon">📡</span>
        <p class="feature-title">Rilevamento postura</p>
        <p>Sensori giroscopici sul petto rilevano in tempo reale la posizione del paziente: supino, lato A, lato B.</p>
      </div>
      <div class="grid__item">
        <span class="feature-icon">🔔</span>
        <p class="feature-title">Alert automatici</p>
        <p>Due soglie configurabili per struttura. Alert arancio e rosso quando il paziente non si gira oltre il tempo previsto.</p>
      </div>
      <div class="grid__item">
        <span class="feature-icon">🏥</span>
        <p class="feature-title">Multi-struttura</p>
        <p>Dashboard distinta per superadmin, responsabili di struttura e operatori sanitari.</p>
      </div>
      <div class="grid__item">
        <span class="feature-icon">🔋</span>
        <p class="feature-title">Stato device</p>
        <p>Monitoraggio batteria, intensità segnale e pulsante di emergenza integrato nel sensore.</p>
      </div>
      <div class="grid__item">
        <span class="feature-icon">📊</span>
        <p class="feature-title">Storico posizioni</p>
        <p>Log completo dei cambi di postura con durata. Report per turno e per paziente.</p>
      </div>
      <div class="grid__item">
        <span class="feature-icon">⚡</span>
        <p class="feature-title">Zero installazioni</p>
        <p>Accesso via browser da qualsiasi dispositivo. Nessun software da installare in struttura.</p>
      </div>
    </div>
  </section>

  <!-- ALERT PREVIEW -->
  <section class="alert-section anim-fadein anim-delay-4">
    <p class="section-label">Dashboard operatore · anteprima</p>
    <div class="table-stack">
      <div class="table-row">
        <span class="pill pill--ok table-row__pill">OK</span>
        <span class="table-row__label">Stanza 3 · Letto A — Posizione: Lato A</span>
        <span class="table-row__meta">aggiornato 00:42</span>
      </div>
      <div class="table-row">
        <span class="pill pill--warn table-row__pill">Arancio</span>
        <span class="table-row__label">Stanza 7 · Letto B — Posizione: Supina da 22 min</span>
        <span class="table-row__meta">alert 01:18</span>
      </div>
      <div class="table-row">
        <span class="pill pill--red table-row__pill">Rosso</span>
        <span class="table-row__label">Stanza 12 · Letto A — Posizione: Supina da 47 min</span>
        <span class="table-row__meta">alert 01:03</span>
      </div>
      <div class="table-row">
        <span class="pill pill--ok table-row__pill">OK</span>
        <span class="table-row__label">Stanza 2 · Letto A — Posizione: Supina</span>
        <span class="table-row__meta">aggiornato 01:55</span>
      </div>
      <div class="table-row">
        <span class="pill pill--warn table-row__pill">Batteria</span>
        <span class="table-row__label">Stanza 9 · Letto B — Batteria 12%</span>
        <span class="table-row__meta">alert 00:30</span>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="anim-fadein anim-delay-5">
    <span>© <?= date('Y') ?> GIRA · <a href="https://tischedo.it" target="_blank">tischedo.it</a></span>
    <span>gira.tischedo.it · Tutti i diritti riservati</span>
  </footer>

</div>

</body>
</html>
