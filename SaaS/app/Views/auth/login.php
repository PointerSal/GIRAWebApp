<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — GIRA Care Monitor</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/gira.css"/>
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
      letter-spacing: 0.05em;
    }

    .auth-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: var(--space-xl);
    }

    .auth-card .section-label {
      margin-bottom: var(--space-lg);
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

  <!-- LOGO -->
  <div class="auth-logo">
    <div class="logo-wrap">
      <div class="logo-mark">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 2a8 8 0 0 1 8 8 8 8 0 0 1-8 8 8 8 0 0 1-8-8 8 8 0 0 1 8-8zm0 3a5 5 0 1 0 0 10A5 5 0 0 0 12 7zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
        </svg>
      </div>
      <span class="logo-text">GI<span>RA</span></span>
    </div>
    <p>Care Monitor · RSA</p>
  </div>

  <!-- CARD -->
  <div class="auth-card">
    <p class="section-label">Accesso alla piattaforma</p>

    <?php if (!empty($errore)): ?>
      <div class="alert-errore"><?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/auth/login-post" method="POST">

      <div class="form-group">
        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail"
               placeholder="nome@struttura.it"
               value="<?= htmlspecialchars($_POST['mail'] ?? '') ?>"
               required autofocus autocomplete="email"/>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••"
               required autocomplete="current-password"/>
      </div>

      <button type="submit" class="btn btn--primary btn-block">Accedi</button>

    </form>
  </div>

  <div class="auth-footer">
    © 2026 GIRA · <a href="https://tischedo.it" target="_blank">tischedo.it</a>
  </div>

</div>

</body>
</html>
