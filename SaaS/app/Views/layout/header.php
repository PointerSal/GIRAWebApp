<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title ?? 'GIRA · Care Monitor') ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/gira.css" />
  <link rel="manifest" href="<?= APP_URL ?>/manifest.json" />
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/img/gira_192x192.png" />
  <meta name="theme-color" content="#0b0f0e" />
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('<?= APP_URL ?>/sw.js');
    }
  </script>
  <style>
    /* ── APP LAYOUT ─────────────────────────────────────────── */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    body::before,
    body::after {
      display: none;
    }

    /* disabilita griglia landing */

    .app-wrapper {
      display: flex;
      flex: 1;
      min-height: 100vh;
    }

    /* ── TOPBAR ──────────────────────────────────────────────── */
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 56px;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 var(--space-lg);
      gap: var(--space-md);
      z-index: 50;
    }

    .topbar-brand {
      font-family: var(--font-serif);
      font-size: 1.2rem;
      letter-spacing: 0.08em;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .topbar-brand span {
      color: var(--green);
    }

    .topbar-badge {
      font-size: 0.6rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      background: var(--red);
      /* ← era var(--green) */
      color: var(--bg);
      padding: 2px 8px;
      border-radius: 2px;
      font-family: var(--font-mono);
    }

    .topbar-badge--medico {
      background: var(--blue);
      color: #000 !important;
    }

    .topbar-badge--utente {
      background: var(--muted);
      color: var(--bg);
    }

    .topbar-badge--admin {
      background: var(--amber);
      color: #000 !important;
    }

    .topbar-user {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: var(--space-md);
      font-size: 0.78rem;
      color: var(--muted);
    }

    .topbar-esci {
      font-size: 0.7rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
      border: 1px solid var(--border);
      padding: 4px 12px;
      border-radius: var(--radius-sm);
      transition: border-color var(--transition), color var(--transition);
    }

    .topbar-esci:hover {
      border-color: var(--red);
      color: var(--red);
    }

    .btn-hamburger {
      display: none;
      background: none;
      border: none;
      cursor: pointer;
      color: var(--muted);
      font-size: 1.3rem;
      padding: 4px;
      line-height: 1;
    }

    /* ── SIDEBAR ─────────────────────────────────────────────── */
    .sidebar {
      position: fixed;
      top: 56px;
      left: 0;
      width: 220px;
      height: calc(100vh - 56px);
      background: var(--surface);
      border-right: 1px solid var(--border);
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      z-index: 40;
    }

    .sidebar-nav {
      flex: 1;
      padding: var(--space-md) 0;
    }

    .nav-sezione {
      font-size: 0.62rem;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      padding: var(--space-md) var(--space-lg) var(--space-xs);
    }

    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px var(--space-lg);
      font-size: 0.82rem;
      color: var(--muted);
      text-decoration: none;
      border-left: 3px solid transparent;
      transition: background var(--transition), color var(--transition);
    }

    .sidebar-nav a:hover {
      background: var(--surface-hover);
      color: var(--text);
    }

    .sidebar-nav a.attivo {
      background: var(--surface-hover);
      color: var(--green);
      border-left-color: var(--green);
    }

    .sidebar-footer {
      border-top: 1px solid var(--border);
      padding: var(--space-md) var(--space-lg);
    }

    .pwa-install {
      display: none;
      margin-bottom: var(--space-md);
    }

    /* ── MAIN CONTENT ────────────────────────────────────────── */
    .main-content {
      margin-top: 56px;
      margin-left: 220px;
      flex: 1;
      padding: var(--space-xl) var(--space-lg);
      min-width: 0;
    }

    /* ── PAGE HEADER ─────────────────────────────────────────── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: var(--space-xl);
      flex-wrap: wrap;
      gap: var(--space-md);
    }

    .page-header h1 {
      font-family: var(--font-serif);
      font-size: 1.8rem;
      color: var(--white);
    }

    .page-header-sub {
      font-size: 0.75rem;
      color: var(--muted);
      margin-top: 2px;
    }

    /* ── STAT CARDS ──────────────────────────────────────────── */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1px;
      background: var(--border);
      border: 1px solid var(--border);
      margin-bottom: var(--space-xl);
    }

    .stat-card {
      background: var(--surface);
      padding: var(--space-lg);
      transition: background var(--transition-slow);
    }

    .stat-card:hover {
      background: var(--surface-hover);
    }

    .stat-card.clickable {
      cursor: pointer;
    }

    .stat-label {
      font-size: 0.65rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: var(--space-sm);
    }

    .stat-value {
      font-family: var(--font-serif);
      font-size: 2rem;
      color: var(--white);
      line-height: 1;
      margin-bottom: var(--space-xs);
    }

    .stat-sub {
      font-size: 0.7rem;
      color: var(--muted);
    }

    /* ── ALERT FLASH ─────────────────────────────────────────── */
    .alert-flash {
      padding: 10px 16px;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      margin-bottom: var(--space-md);
      border: 1px solid;
    }

    .alert-flash--ok {
      background: var(--alert-ok-bg);
      color: var(--green);
      border-color: var(--green-dim);
    }

    .alert-flash--errore {
      background: var(--alert-red-bg);
      color: var(--red);
      border-color: var(--red);
    }

    /* ── MOBILE OVERLAY + DRAWER ─────────────────────────────── */
    .mob-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 99;
    }

    .mob-overlay.open {
      display: block;
    }

    .mob-drawer {
      position: fixed;
      top: 0;
      right: 0;
      width: 260px;
      height: 100%;
      background: var(--surface);
      border-left: 1px solid var(--border);
      z-index: 100;
      transform: translateX(100%);
      transition: transform 0.25s ease;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }

    .mob-drawer.open {
      transform: translateX(0);
    }

    .mob-drawer .nav-sezione {
      padding: var(--space-md) var(--space-lg) var(--space-xs);
    }

    .mob-drawer a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px var(--space-lg);
      font-size: 0.85rem;
      color: var(--muted);
      text-decoration: none;
      border-left: 3px solid transparent;
      transition: background var(--transition), color var(--transition);
    }

    .mob-drawer a:hover,
    .mob-drawer a.attivo {
      background: var(--surface-hover);
      color: var(--green);
      border-left-color: var(--green);
    }

    .mob-drawer-footer {
      margin-top: auto;
      border-top: 1px solid var(--border);
      padding: var(--space-md) var(--space-lg);
    }

    .mob-drawer-footer a {
      color: var(--red) !important;
      border-left: none !important;
    }

    /* ── RESPONSIVE ──────────────────────────────────────────── */
    @media (max-width: 1023px) {
      .sidebar {
        display: none;
      }

      .main-content {
        margin-left: 0;
      }

      .btn-hamburger {
        display: block;
      }

      .topbar-esci {
        display: none;
      }
    }

    @media (min-width: 1024px) {

      .mob-drawer,
      .mob-overlay {
        display: none !important;
      }
    }

    @media (max-width: 600px) {
      .main-content {
        padding: var(--space-md);
      }

      .stat-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
  </style>
  <?php if (isset($extra_css)) echo $extra_css; ?>
</head>

<body>

  <!-- ── MOBILE OVERLAY + DRAWER ──────────────────────────────── -->
  <div class="mob-overlay" id="mob-overlay" onclick="chiudiMenu()"></div>
  <div class="mob-drawer" id="mob-drawer">
    <nav>
      <?php include VIEW_PATH . 'layout/_nav.php'; ?>
    </nav>
    <div class="mob-drawer-footer">
      <a href="javascript:void(0);" id="install-btn-mob" style="display:none;">📲 Installa app</a>
      <a href="<?= APP_URL ?>/auth/profilo">⚙ Profilo</a>
      <a href="<?= APP_URL ?>/auth/logout">🚪 Esci</a>
    </div>
  </div>

  <div class="app-wrapper">

    <!-- ── TOPBAR ───────────────────────────────────────────────── -->
    <header class="topbar">
      <div class="topbar-brand">
        <span style="color:var(--text);">GI</span><span>RA</span>
        <?php
        $ruolo = Auth::ruolo();
        if ($ruolo === RUOLO_SUPERADMIN):
        ?>
          <span class="topbar-badge">Superadmin</span>
        <?php elseif ($ruolo === RUOLO_ADMIN): ?>
          <span class="topbar-badge topbar-badge--admin">Admin</span>
        <?php elseif ($ruolo === RUOLO_MEDICO): ?>
          <span class="topbar-badge topbar-badge--medico">Medico</span>
        <?php elseif ($ruolo === RUOLO_UTENTE): ?>
          <span class="topbar-badge topbar-badge--utente">Operatore</span>
        <?php endif; ?>
      </div>

      <div class="topbar-user">
        <?php
        $strutture_utente = Auth::strutture_accessibili();
        if (!Auth::isSuperadmin() && count($strutture_utente) > 1):
          $attiva = Auth::struttura_attiva();
          // Carica nomi strutture
          $ph = implode(',', array_fill(0, count($strutture_utente), '?'));
          $stmt = Database::getInstance()->prepare(
            "SELECT id, ragione_sociale FROM gir_struttura WHERE id IN ($ph) AND attiva = 1"
          );
          $stmt->execute($strutture_utente);
          $strutture_lista = $stmt->fetchAll();
        ?>
          <form method="POST" action="<?= APP_URL ?>/struttura-attiva/set" style="margin:0;">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?>" />
            <select name="id_struttura"
              onchange="this.form.submit()"
              style="background:var(--surface); border:1px solid var(--border); color:var(--text);
                 font-family:var(--font-mono); font-size:0.72rem; padding:4px 8px;
                 border-radius:var(--radius-sm); cursor:pointer;">
              <?php foreach ($strutture_lista as $s): ?>
                <option value="<?= $s['id'] ?>"
                  <?= (int)$s['id'] === $attiva ? 'selected' : '' ?>>
                  🏥 <?= htmlspecialchars($s['ragione_sociale']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>
        <span><?= htmlspecialchars(Auth::utente()['nome'] . ' ' . Auth::utente()['cognome']) ?></span>
        <button class="tema-toggle" id="tema-toggle" title="Cambia tema" onclick="toggleTema()">☀</button>
        <a href="<?= APP_URL ?>/auth/logout" class="topbar-esci">Esci</a>
        <button class="btn-hamburger" onclick="apriMenu()" aria-label="Menu">☰</button>
      </div>
    </header>

    <!-- ── SIDEBAR DESKTOP ──────────────────────────────────────── -->
    <aside class="sidebar">
      <nav class="sidebar-nav">
        <?php include VIEW_PATH . 'layout/_nav.php'; ?>
      </nav>
      <div class="sidebar-footer">
        <div class="pwa-install" id="pwa-install-desk">
          <div class="nav-sezione" style="padding:0 0 var(--space-xs);">App</div>
          <a href="javascript:void(0);" id="install-btn-desk" style="padding:0; font-size:0.78rem;">📲 Installa app</a>
        </div>
        <a href="<?= APP_URL ?>/auth/profilo"
          style="display:block; font-size:0.72rem; color:var(--muted); text-decoration:none; padding: 4px 0;"
          onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--muted)'">
          ⚙ Profilo
        </a>
      </div>
    </aside>

    <!-- ── MAIN CONTENT (aperto qui, chiuso in footer.php) ──────── -->
    <main class="main-content">

      <?php
      // Flash messages
      if (!empty($_SESSION['successo'])):
      ?>
        <div class="alert-flash alert-flash--ok"><?= htmlspecialchars($_SESSION['successo']) ?></div>
      <?php unset($_SESSION['successo']);
      endif; ?>

      <?php if (!empty($_SESSION['errore'])): ?>
        <div class="alert-flash alert-flash--errore"><?= htmlspecialchars($_SESSION['errore']) ?></div>
      <?php unset($_SESSION['errore']);
      endif; ?>

      <script>
        function apriMenu() {
          document.getElementById('mob-drawer').classList.add('open');
          document.getElementById('mob-overlay').classList.add('open');
          document.body.style.overflow = 'hidden';
        }

        function chiudiMenu() {
          document.getElementById('mob-drawer').classList.remove('open');
          document.getElementById('mob-overlay').classList.remove('open');
          document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => {
          if (e.key === 'Escape') chiudiMenu();
        });

        // PWA install
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', e => {
          e.preventDefault();
          deferredPrompt = e;
          const s = document.getElementById('pwa-install-desk');
          if (s) s.style.display = 'block';
          const m = document.getElementById('install-btn-mob');
          if (m) m.style.display = 'block';
        });
        const handleInstall = async () => {
          if (!deferredPrompt) return;
          deferredPrompt.prompt();
          const {
            outcome
          } = await deferredPrompt.userChoice;
          deferredPrompt = null;
          const s = document.getElementById('pwa-install-desk');
          if (s) s.style.display = 'none';
          const m = document.getElementById('install-btn-mob');
          if (m) m.style.display = 'none';
        };
        document.addEventListener('DOMContentLoaded', () => {
          document.getElementById('install-btn-desk')?.addEventListener('click', handleInstall);
          document.getElementById('install-btn-mob')?.addEventListener('click', handleInstall);
        });
        window.addEventListener('appinstalled', () => {
          deferredPrompt = null;
        });
        // ── TEMA LIGHT/DARK ──────────────────────────────────────
        (function() {
          const STORAGE_KEY = 'gira-tema';
          const body = document.body;
          const toggle = document.getElementById('tema-toggle');

          function applicaTema(light) {
            if (light) {
              body.classList.add('tema-light');
              if (toggle) toggle.textContent = '☾';
            } else {
              body.classList.remove('tema-light');
              if (toggle) toggle.textContent = '☀';
            }
          }

          // Applica tema salvato al caricamento
          const saved = localStorage.getItem(STORAGE_KEY);
          applicaTema(saved === 'light');

          // Toggle
          window.toggleTema = function() {
            const isLight = body.classList.contains('tema-light');
            localStorage.setItem(STORAGE_KEY, isLight ? 'dark' : 'light');
            applicaTema(!isLight);
          };
        })();
      </script>