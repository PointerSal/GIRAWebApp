<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>403 — Accesso negato · GIRA</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/gira.css"/>
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .error-box { text-align:center; position:relative; z-index:1; }
    .error-code { font-family:var(--font-serif); font-size:8rem; color:var(--border); line-height:1; margin-bottom:var(--space-md); }
  </style>
</head>
<body>
<div class="glow-orb"></div>
<div class="error-box anim-fadein anim-delay-1">
  <div class="error-code">403</div>
  <h2 style="margin-bottom:var(--space-md);">Accesso negato</h2>
  <p style="color:var(--muted); margin-bottom:var(--space-xl);">
    Non hai i permessi per accedere a questa pagina.
  </p>
  <a href="<?= APP_URL ?>/dashboard" class="btn btn--primary">← Torna alla dashboard</a>
</div>
</body>
</html>
