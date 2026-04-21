<div class="page-header">
    <h1>Reparti</h1>
</div>

<p class="section-label">Seleziona una struttura</p>
<div class="table-stack">
    <?php foreach ($strutture as $s): ?>
        <div class="table-row">
            <span class="table-row__label">
                <strong><?= htmlspecialchars($s['ragione_sociale']) ?></strong>
            </span>
            <a href="<?= APP_URL ?>/ubicazioni?id_struttura=<?= $s['id'] ?>"
                class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">
                Gestisci →
            </a>
        </div>
    <?php endforeach; ?>
</div>