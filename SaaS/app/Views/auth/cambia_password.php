<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cambia password — GIRA</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/gira.css" />
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: var(--space-lg);
    }

    .auth-box {
      width: 100%;
      max-width: 400px;
      position: relative;
      z-index: 1;
    }

    .auth-logo {
      text-align: center;
      margin-bottom: var(--space-xl);
    }

    .auth-logo .logo-wrap {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      margin-bottom: var(--space-sm);
    }

    .auth-logo p {
      font-size: 0.75rem;
      color: var(--muted);
    }

    .auth-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: var(--space-xl);
    }

    .alert-errore {
      background: var(--alert-red-bg);
      color: var(--red);
      border: 1px solid var(--red);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      font-size: 0.78rem;
      margin-bottom: var(--space-md);
    }

    .alert-ok {
      background: var(--alert-ok-bg);
      color: var(--green);
      border: 1px solid var(--green-dim);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      font-size: 0.78rem;
      margin-bottom: var(--space-md);
    }

    .btn-block {
      width: 100%;
      justify-content: center;
      margin-top: var(--space-lg);
    }

    .auth-footer {
      text-align: center;
      margin-top: var(--space-lg);
      font-size: 0.72rem;
      color: var(--muted);
    }
  </style>
</head>

<body>

  <div class="glow-orb"></div>

  <div class="auth-box anim-fadein anim-delay-1">

    <div class="auth-logo">
      <div class="logo-wrap">
        <div class="logo-mark">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 2a8 8 0 0 1 8 8 8 8 0 0 1-8 8 8 8 0 0 1-8-8 8 8 0 0 1 8-8zm0 3a5 5 0 1 0 0 10A5 5 0 0 0 12 7zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6z" />
          </svg>
        </div>
        <span class="logo-text">GI<span>RA</span></span>
      </div>
      <p>
        <?php if (!empty(Auth::utente()['deve_cambiare_pwd'])): ?>
          Devi impostare una nuova password prima di continuare.
        <?php else: ?>
          Modifica la tua password
        <?php endif; ?>
      </p>
    </div>

    <div class="auth-card">
      <p class="section-label">Cambia password</p>

      <?php if (!empty($errore)): ?>
        <div class="alert-errore"><?= htmlspecialchars($errore) ?></div>
      <?php endif; ?>
      <?php if (!empty($successo)): ?>
        <div class="alert-ok"><?= htmlspecialchars($successo) ?></div>
      <?php endif; ?>

      <form action="<?= APP_URL ?>/auth/cambia-password-post" method="POST">

        <div class="form-group">
          <label for="vecchia">Password attuale</label>
          <input type="password" id="vecchia" name="vecchia"
            placeholder="••••••••"
            required autocomplete="current-password" />
        </div>

        <div class="form-group">
          <label for="nuova">Nuova password</label>
          <input type="password" id="nuova" name="nuova"
            placeholder="Minimo 8 caratteri"
            required autocomplete="new-password" />
        </div>

        <div class="form-group">
          <label for="nuova2">Conferma nuova password</label>
          <input type="password" id="nuova2" name="nuova2"
            placeholder="Ripeti la nuova password"
            required autocomplete="new-password" />
        </div>

        <button type="submit" class="btn btn--primary btn-block">Aggiorna password</button>

      </form>
    </div>

    <div class="auth-footer">
      <a href="<?= APP_URL ?>/auth/logout">Esci dall'account</a>
    </div>

    <?php if (empty(Auth::utente()['deve_cambiare_pwd'])): ?>
      <div class="auth-footer">
        <a href="<?= APP_URL ?>/dashboard">← Torna alla dashboard</a>
      </div>
    <?php endif; ?>

  </div>

</body>

</html>