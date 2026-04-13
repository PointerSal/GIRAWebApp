<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione — Asset Tracking</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>

<body class="auth-body">

    <div class="auth-box auth-box--wide">
        <div class="auth-logo">
            <h1><a href="https://at.tischedo.it" style="text-decoration:none; color:inherit;">Asset<span style="color:var(--accent);">Track</span></a></h1>
            <p>Registra la tua azienda — 30 giorni gratuiti</p>
        </div>

        <?php if (!empty($errore)): ?>
            <div class="alert alert-errore"><?= $errore ?></div>
        <?php endif; ?>

        <?php if (!empty($successo)): ?>
            <div class="alert alert-successo"><?= $successo ?></div>
        <?php endif; ?>

        <?php if (empty($successo)): ?>
            <form action="<?= BASE_URL ?>/auth/registra-post" method="POST">

                <h2 class="form-sezione">Dati azienda</h2>

                <div class="campo">
                    <label for="ragione_sociale">Ragione sociale *</label>
                    <input type="text" id="ragione_sociale" name="ragione_sociale"
                        value="<?= htmlspecialchars($form_data['ragione_sociale'] ?? '') ?>"
                        required>
                </div>

                <div class="campo">
                    <label for="partita_iva">Partita IVA *</label>
                    <input type="text" id="partita_iva" name="partita_iva"
                        value="<?= htmlspecialchars($form_data['partita_iva'] ?? '') ?>"
                        maxlength="20" required>
                </div>

                <div class="campo">
                    <label for="indirizzo">Indirizzo *</label>
                    <input type="text" id="indirizzo" name="indirizzo"
                        value="<?= htmlspecialchars($form_data['indirizzo'] ?? '') ?>"
                        required>
                </div>

                <div class="campo">
                    <label for="email_azienda">Email aziendale</label>
                    <input type="email" id="email_azienda" name="email_azienda"
                        value="<?= htmlspecialchars($form_data['email_azienda'] ?? '') ?>">
                </div>

                <h2 class="form-sezione">Dati referente (amministratore)</h2>

                <div class="campo-gruppo">
                    <div class="campo">
                        <label for="nome">Nome *</label>
                        <input type="text" id="nome" name="nome"
                            value="<?= htmlspecialchars($form_data['nome'] ?? '') ?>"
                            required>
                    </div>
                    <div class="campo">
                        <label for="cognome">Cognome *</label>
                        <input type="text" id="cognome" name="cognome"
                            value="<?= htmlspecialchars($form_data['cognome'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <div class="campo">
                    <label for="gsm">Numero GSM * <small>(sarà usato per il login)</small></label>
                    <input type="tel" id="gsm" name="gsm"
                        value="<?= htmlspecialchars($form_data['gsm'] ?? '') ?>"
                        placeholder="3331234567" required>
                    <small style="color:var(--text-muted); margin-top:0.25rem; display:block;">
                        Inserisci senza prefisso internazionale (+39)
                    </small>
                </div>

                <div class="campo">
                    <label for="email">Email referente * <small>(per le comunicazioni)</small></label>
                    <input type="email" id="email" name="email"
                        value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
                        required>
                </div>

                <div class="campo-gruppo">
                    <div class="campo">
                        <label for="password">Password * <small>(min. 8 caratteri)</small></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="campo">
                        <label for="password2">Conferma password *</label>
                        <input type="password" id="password2" name="password2" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Invia richiesta di registrazione
                </button>

            </form>
        <?php endif; ?>

        <div class="auth-footer">
            Hai già un account? <a href="<?= BASE_URL ?>/auth/login">Accedi</a>
        </div>
    </div>

</body>

</html>