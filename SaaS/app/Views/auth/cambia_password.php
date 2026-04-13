<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambia password — Asset Tracking</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="auth-body">

<div class="auth-box">
    <div class="auth-logo">
        <h1>Cambia password</h1>
        <p>Il tuo amministratore ha reimpostato la tua password.<br>
           Scegline una nuova per continuare.</p>
    </div>

    <?php if (!empty($errore)): ?>
        <div class="alert alert-errore"><?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/auth/cambia-password" method="POST">

        <div class="campo">
            <label for="vecchia">Password attuale</label>
            <input type="password" id="vecchia" name="vecchia"
                   required autofocus>
        </div>

        <div class="campo">
            <label for="nuova">Nuova password <small>(min. 8 caratteri)</small></label>
            <input type="password" id="nuova" name="nuova" required>
        </div>

        <div class="campo">
            <label for="nuova2">Conferma nuova password</label>
            <input type="password" id="nuova2" name="nuova2" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            Salva nuova password
        </button>

    </form>
</div>

</body>
</html>
