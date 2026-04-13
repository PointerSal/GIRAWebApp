<?php
// ============================================================
//  GIRA · app/Views/strutture/show.php
// ============================================================
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($struttura['ragione_sociale']) ?></h1>
    <div class="page-header-sub">P.IVA <?= htmlspecialchars($struttura['partita_iva']) ?></div>
  </div>
  <div class="flex-center gap-sm">
    <a href="<?= APP_URL ?>/strutture/modifica/<?= $struttura['id'] ?>"
       class="btn btn--outline">Modifica</a>
    <?php if ($struttura['attiva']): ?>
      <a href="<?= APP_URL ?>/strutture/sospendi/<?= $struttura['id'] ?>"
         class="btn btn--danger"
         onclick="return confirm('Sospendere questa struttura?')">Sospendi</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/strutture/attiva/<?= $struttura['id'] ?>"
         class="btn btn--outline" style="color:var(--green); border-color:var(--green);">Riattiva</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/strutture" class="btn btn--outline">← Tutte le strutture</a>
  </div>
</div>

<!-- Info struttura -->
<div class="grid grid--2" style="margin-bottom:var(--space-xl);">
  <div class="card">
    <p class="section-label">Anagrafica</p>
    <table style="width:100%; font-size:0.82rem; border-collapse:collapse;">
      <?php
        $campi = [
          'Stato'     => $struttura['attiva'] ? '<span class="pill pill--ok">Attiva</span>' : '<span class="pill pill--muted">Sospesa</span>',
          'Indirizzo' => htmlspecialchars($struttura['indirizzo'] ?? '—'),
          'Telefono'  => htmlspecialchars($struttura['telefono']  ?? '—'),
          'Email'     => htmlspecialchars($struttura['mail']      ?? '—'),
          'Creata il' => date('d/m/Y', strtotime($struttura['creata_il'])),
        ];
        foreach ($campi as $label => $valore):
      ?>
        <tr>
          <td style="color:var(--muted); padding:6px 0; width:100px;"><?= $label ?></td>
          <td style="color:var(--text);"><?= $valore ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <p class="section-label">Subscription</p>
    <?php if ($sub): ?>
      <table style="width:100%; font-size:0.82rem; border-collapse:collapse;">
        <?php
          $campi_sub = [
            'Piano'     => '<span class="pill pill--ok">' . $sub['piano'] . '</span>',
            'Stato'     => htmlspecialchars($sub['stato']),
            'Inizio'    => date('d/m/Y', strtotime($sub['inizio_il'])),
            'Scadenza'  => $sub['fine_il'] ? date('d/m/Y', strtotime($sub['fine_il'])) : 'Illimitata',
            'Max device'=> $sub['max_device'] ?? 'Illimitati',
          ];
          foreach ($campi_sub as $label => $valore):
        ?>
          <tr>
            <td style="color:var(--muted); padding:6px 0; width:100px;"><?= $label ?></td>
            <td style="color:var(--text);"><?= $valore ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p style="color:var(--muted); font-size:0.82rem;">Nessuna subscription attiva.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Ubicazioni -->
<div class="card" style="margin-bottom:var(--space-xl);">
  <div class="flex-between mb-md">
    <p class="section-label" style="margin:0;">Ubicazioni (<?= count($ubicazioni ?? []) ?>)</p>
    <a href="<?= APP_URL ?>/ubicazioni?id_struttura=<?= $struttura['id'] ?>"
       class="btn btn--outline" style="font-size:0.7rem; padding:6px 14px;">Gestisci →</a>
  </div>
  <?php if (empty($ubicazioni)): ?>
    <div class="table-row">
      <span class="table-row__label text-muted">Nessuna ubicazione definita.</span>
    </div>
  <?php else: ?>
    <div class="table-stack">
      <?php foreach ($ubicazioni as $u): ?>
      <div class="table-row">
        <span class="table-row__label">
          <strong><?= htmlspecialchars($u['area']) ?></strong>
          <?php if ($u['subarea']): ?>
            <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
              · <?= htmlspecialchars($u['subarea']) ?>
            </span>
          <?php endif; ?>
        </span>
        <span class="table-row__meta">📡 <?= $u['tot_device'] ?> device</span>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Device -->
<div class="card" style="margin-bottom:var(--space-xl);">
  <div class="flex-between mb-md">
    <p class="section-label" style="margin:0;">Device (<?= count($device) ?>)</p>
    <a href="<?= APP_URL ?>/device/crea?id_struttura=<?= $struttura['id'] ?>"
       class="btn btn--primary" style="font-size:0.7rem; padding:6px 14px;">+ Device</a>
  </div>
  <div class="table-stack">
    <?php if (empty($device)): ?>
      <div class="table-row">
        <span class="table-row__label text-muted">Nessun device registrato.</span>
      </div>
    <?php else: ?>
      <?php foreach ($device as $d): ?>
      <div class="table-row">
        <span class="status-dot <?= $d['attivo'] ? 'status-dot--ok' : 'status-dot--off' ?>"></span>
        <span class="table-row__label">
          <strong><?= htmlspecialchars($d['label'] ?? $d['mac']) ?></strong>
          <span style="color:var(--muted); font-size:0.72rem; margin-left:8px;">
            <?= htmlspecialchars($d['mac']) ?>
          </span>
        </span>
        <span class="table-row__meta">
          <?= $d['area'] ? htmlspecialchars($d['area'] . ($d['subarea'] ? ' · ' . $d['subarea'] : '')) : '—' ?>
        </span>
        <span class="table-row__meta">
          <?= $d['posizione'] ?? '—' ?>
        </span>
        <a href="<?= APP_URL ?>/device/show/<?= $d['id'] ?>"
           class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">→</a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Utenti -->
<div class="card">
  <div class="flex-between mb-md">
    <p class="section-label" style="margin:0;">Utenti (<?= count($utenti) ?>)</p>
    <a href="<?= APP_URL ?>/utenti/crea?id_struttura=<?= $struttura['id'] ?>"
       class="btn btn--primary" style="font-size:0.7rem; padding:6px 14px;">+ Utente</a>
  </div>
  <div class="table-stack">
    <?php if (empty($utenti)): ?>
      <div class="table-row">
        <span class="table-row__label text-muted">Nessun utente associato.</span>
      </div>
    <?php else: ?>
      <?php foreach ($utenti as $u): ?>
      <div class="table-row">
        <span class="table-row__label">
          <strong><?= htmlspecialchars($u['nome'] . ' ' . $u['cognome']) ?></strong>
        </span>
        <span class="pill pill--muted"><?= htmlspecialchars($u['ruolo_nome']) ?></span>
        <span class="table-row__meta"><?= htmlspecialchars($u['mail']) ?></span>
        <a href="<?= APP_URL ?>/utenti/modifica/<?= $u['id'] ?>"
           class="btn btn--outline" style="font-size:0.68rem; padding:3px 10px;">Modifica</a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
