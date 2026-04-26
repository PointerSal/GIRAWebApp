<?php
// ============================================================
//  GIRA · app/Views/layout/_bed_icon.php
//  Icona letto SVG colorata in base allo stato alert
//
//  Uso: include VIEW_PATH . 'layout/_bed_icon.php';
//  Variabili richieste:
//    $bed_color  — colore riempimento (es. 'var(--red)')
//    $bed_size   — dimensione in px (default 44)
// ============================================================
$bed_size  = $bed_size ?? 44;
$bed_color = $bed_color ?? 'var(--muted)';
?>
<svg width="<?= $bed_size ?>" height="<?= round($bed_size * 0.85) ?>"
     viewBox="0 0 160 130" fill="none"
     xmlns="http://www.w3.org/2000/svg"
     style="flex-shrink:0; filter:drop-shadow(0 1px 2px rgba(0,0,0,0.3));"
     aria-hidden="true">

  <style>
    .bed-stroke { stroke: var(--bed-stroke, rgba(255,255,255,0.6)); }
    .bed-stroke-soft { stroke: var(--bed-stroke, rgba(255,255,255,0.5)); fill: none; }
    .bed-flebo { fill: rgba(200,200,200,0.3); stroke: var(--bed-stroke, rgba(255,255,255,0.5)); }
  </style>

  <!-- Struttura letto -->
  <rect x="20" y="48" width="75" height="45" rx="9"
        class="bed-stroke" stroke-width="4" fill="none"/>
  <rect x="18" y="88" width="82" height="8" rx="3"
        class="bed-stroke" stroke-width="4" fill="none"/>
  <line x1="18" y1="97" x2="18" y2="112"
        class="bed-stroke" stroke-width="4" stroke-linecap="round"/>
  <line x1="98" y1="97" x2="98" y2="112"
        class="bed-stroke" stroke-width="4" stroke-linecap="round"/>
  <line x1="20" y1="59" x2="39" y2="19"
        class="bed-stroke" stroke-width="4" stroke-linecap="round"/>
  <line x1="39" y1="19" x2="95" y2="19"
        class="bed-stroke" stroke-width="4" stroke-linecap="round"/>

  <!-- Paziente (colorato) -->
  <circle cx="74" cy="40" r="14"
          fill="<?= $bed_color ?>" class="bed-stroke" stroke-width="4"/>
  <rect x="30" y="32" width="44" height="14" rx="7"
          fill="<?= $bed_color ?>" class="bed-stroke" stroke-width="4"/>
  <path d="M48 49C38 49 28 57 28 69V79H88V69C88 57 78 49 68 49H48Z"
          fill="<?= $bed_color ?>" class="bed-stroke" stroke-width="4"/>

  <!-- Piantana flebo -->
  <path d="M100 28H111L116 36V93"
        class="bed-stroke-soft" stroke-width="4"
        stroke-linecap="round" stroke-linejoin="round"/>
  <rect x="111" y="32" width="10" height="14" rx="2"
        class="bed-flebo" stroke-width="3"/>
</svg>
