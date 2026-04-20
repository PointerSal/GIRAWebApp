<?php

declare(strict_types=1);

// ============================================================
//  GIRA · ingest.php
//  Endpoint HTTP POST per il gateway BLE
//
//  Flusso:
//  1. Valida API key + body JSON
//  2. Per ogni device nel payload:
//     a. Verifica MAC in gir_device
//     b. Decodifica payload BLE (x, y, z, stato)
//     c. Scrive dati grezzi in gir_raw (ultimi 24h)
//     d. Calcola posizione (SUPINO / LATO_A / LATO_B / PRONO)
//     e. Aggiorna gir_device_stato
//     f. Gestisce cambio posizione con doppia isteresi:
//        - campioni degli ultimi MIN_POSIZIONE_MINUTI minuti
//        - almeno SOGLIA_CONFERMA_PERC% devono concordare con la nuova posizione
//     g. Pulizia gir_raw — sempre, tieni solo ultimi MIN_POSIZIONE_MINUTI+1 minuti
//     h. Analizza durata posizione attuale → genera alert se necessario
// ============================================================

require_once __DIR__ . '/../SaaS/gira/app/Config/config.php';
require_once __DIR__ . '/../SaaS/gira/app/Core/Database.php';
require_once __DIR__ . '/../SaaS/gira/vendor/autoload.php';
require_once __DIR__ . '/../SaaS/gira/app/Core/NotificationService.php';

// ── 1. API KEY ───────────────────────────────────────────────
$headers = getallheaders();
//$apiKey  = $headers['X-Api-Key'] ?? $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';

$apiKey = $headers['X-Api-Key']
    ?? $headers['X-API-Key']
    ?? $headers['x-api-key']
    ?? $_GET['key']           // TODO: rimuovere in produzione, usare solo header
    ?? '';

if ($apiKey !== INGEST_API_KEY) {
    respond(401, ['ok' => false, 'error' => 'unauthorized']);
}

// ── 2. BODY ──────────────────────────────────────────────────
$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    respond(400, ['ok' => false, 'error' => 'empty body']);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, ['ok' => false, 'error' => 'invalid json']);
}

// Campi obbligatori del payload gateway
foreach (['v', 'mid', 'time', 'ip', 'mac', 'devices', 'rssi'] as $k) {
    if (!array_key_exists($k, $data)) {
        respond(400, ['ok' => false, 'error' => "missing field: $k"]);
    }
}
if (!is_array($data['devices'])) {
    respond(400, ['ok' => false, 'error' => 'devices must be array']);
}

// ── 3. DB ────────────────────────────────────────────────────
$db = Database::getInstance();

// ── 4. PROCESSA OGNI DEVICE ──────────────────────────────────
foreach ($data['devices'] as $dev) {

    // Formato atteso: [type, mac, rssi, hex_payload]
    if (!is_array($dev) || count($dev) < 4) continue;

    $mac  = normalize_mac((string)$dev[1]);
    $rssi = (int)$dev[2];
    $hex  = strtoupper(trim((string)$dev[3]));

    // ── a. Verifica MAC registrato ───────────────────────────
    $device = $db->prepare(
        "SELECT d.id, d.id_struttura,
                COALESCE(s.soglia_arancio_min, :def_arancio) AS soglia_arancio,
                COALESCE(s.soglia_rosso_min,   :def_rosso)   AS soglia_rosso,
                COALESCE(s.campioni_conferma,  :def_camp)    AS campioni_conferma,
                COALESCE(s.silenzio_da,        :def_sil_da)  AS silenzio_da,
                COALESCE(s.silenzio_a,         :def_sil_a)   AS silenzio_a
         FROM gir_device d
         LEFT JOIN gir_soglie s ON s.id_struttura = d.id_struttura
         WHERE d.mac = :mac AND d.attivo = 1
         LIMIT 1"
    );
    $device->execute([
        ':mac'         => $mac,
        ':def_arancio' => ALERT_ARANCIO_MIN,
        ':def_rosso'   => ALERT_ROSSO_MIN,
        ':def_camp'    => 3,
        ':def_sil_da'  => 22,
        ':def_sil_a'   => 7,
    ]);
    $device = $device->fetch();
    if (!$device) continue; // MAC non registrato o disattivato

    $idDevice     = (int)$device['id'];
    $sogliArancio = (int)$device['soglia_arancio'];
    $sogliaRosso  = (int)$device['soglia_rosso'];
    $campConferma = (int)$device['campioni_conferma'];
    $silenzioDa   = (int)$device['silenzio_da'];
    $silenzioA    = (int)$device['silenzio_a'];

    // ── b. Decodifica payload BLE ────────────────────────────
    $bytes = hex_to_bytes($hex);
    if ($bytes === null) continue;

    $adv = parse_ble_adv($bytes);
    $g   = $adv['manufacturer']['gira'] ?? null;
    if (!is_array($g)) continue; // non è un payload GIRA valido

    $x     = (int)$g['x'];
    $y     = (int)$g['y'];
    $z     = (int)$g['z'];
    $stato = (int)$g['stato'];

    // Decodifica byte stato
    // bit 0:   pulsante (0/1)
    $pulsante = (int)($stato & 0x01);

    $batteria = $g['batteria']; // null = N/A
    //$batteria = (int)round((($stato >> 1) & 0x7F) / 127 * 100);


    // ── c. Scrivi dati grezzi (gir_raw) ─────────────────────
    // Serve per l'isteresi temporale — pulizia automatica in gestisci_posizione()
    $db->prepare(
        "INSERT INTO gir_raw (id_device, x, y, z, ricevuto_alle)
         VALUES (:id, :x, :y, :z, NOW())"
    )->execute([':id' => $idDevice, ':x' => $x, ':y' => $y, ':z' => $z]);

    // ── d. Calcola posizione ─────────────────────────────────
    $posizione = calcola_posizione($x, $y, $z);

    // ── e. Aggiorna stato attuale device ────────────────────
    $db->prepare(
        "INSERT INTO gir_device_stato
            (id_device, posizione, stato_batt, stato_segnale, stato_pulsante,
             ultimo_contatto)
         VALUES
            (:id, :pos, :batt, :rssi, :pulsante, NOW())
         ON DUPLICATE KEY UPDATE
            posizione       = VALUES(posizione),
            stato_batt      = VALUES(stato_batt),
            stato_segnale   = VALUES(stato_segnale),
            stato_pulsante  = VALUES(stato_pulsante),
            ultimo_contatto = NOW()"
    )->execute([
        ':id'      => $idDevice,
        ':pos'     => $posizione,
        ':batt'    => $batteria,
        ':rssi'    => $rssi,
        ':pulsante' => $pulsante,
    ]);

    // ── f. Gestione cambio posizione con doppia isteresi ────
    gestisci_posizione($db, $idDevice, $posizione);

    // ── g. Pulizia gir_raw — ogni MIN_POSIZIONE_MINUTI minuti ───
    //       Un solo file timestamp per tutti i device — molto più leggero
    //       che fare una DELETE ad ogni campione ricevuto
    $lock_pulizia   = sys_get_temp_dir() . '/gira_raw_clean';
    $ultima_pulizia = file_exists($lock_pulizia) ? (int)file_get_contents($lock_pulizia) : 0;

    if (time() - $ultima_pulizia >= MIN_POSIZIONE_MINUTI * 60) {
        $db->prepare(
            "DELETE FROM gir_raw
              WHERE ricevuto_alle < DATE_SUB(NOW(), INTERVAL :minuti MINUTE)"
        )->execute([':minuti' => MIN_POSIZIONE_MINUTI + 1]);
        file_put_contents($lock_pulizia, time());
    }

    // ── h. Analisi alert ─────────────────────────────────────
    analizza_alert(
        $db,
        $idDevice,
        $posizione,
        $batteria,
        $pulsante,
        $sogliArancio,
        $sogliaRosso,
        $silenzioDa,
        $silenzioA
    );
}

respond(200, ['ok' => true]);


// ============================================================
//  POSIZIONE
// ============================================================

/**
 * Calcola la posizione del paziente dagli assi x, y, z.
 * Unità: ~1/1000 g (es. z=1024 ≈ 1g)
 *
 * SUPINO:  z >  700  (petto verso l'alto)
 * PRONO:   z < -700  (petto verso il basso)
 * Zona grigia → SCONOSCIUTO (isteresi)
 */
function calcola_posizione(int $x, int $y, int $z): string
{
    // Asse Z — supino/prono (soglia confermata dai dati reali)
    if ($z >  700) return 'SUPINO';
    if ($z < -700) return 'PRONO';

    // Piano XY — soglia alzata da 500 a 700 (confermata dai dati reali)
    $moduloXY = sqrt($x ** 2 + $y ** 2);

    if ($moduloXY > 700) {
        if (abs($x) >= abs($y)) {
            return $x > 0 ? 'LATO_A' : 'LATO_B';
        } else {
            return $y > 0 ? 'LATO_A' : 'LATO_B';
        }
    }

    // Zona grigia — include transizioni e posizione seduta
    return 'SCONOSCIUTO';
}


/**
 * Gestisce il cambio posizione con doppia isteresi:
 * 1. TEMPORALE  — guarda tutti i campioni grezzi degli ultimi MIN_POSIZIONE_MINUTI minuti
 * 2. PERCENTUALE — almeno SOGLIA_CONFERMA_PERC% dei campioni deve concordare
 *
 * Questo elimina i falsi cambi causati da rumore del sensore.
 * Dopo ogni cambio confermato pulisce gir_raw dei dati non più necessari.
 */
function gestisci_posizione(PDO $db, int $idDevice, string $nuovaPos): void
{
    // SCONOSCIUTO → zona grigia, non aggiornare il log
    if ($nuovaPos === 'SCONOSCIUTO') return;

    // Leggi posizione corrente nel log (record aperto = terminato_alle IS NULL)
    $corrente = $db->prepare(
        "SELECT id, posizione, iniziato_alle
         FROM gir_posizione_log
         WHERE id_device = :id AND terminato_alle IS NULL
         ORDER BY iniziato_alle DESC
         LIMIT 1"
    );
    $corrente->execute([':id' => $idDevice]);
    $corrente = $corrente->fetch();

    // Nessun record aperto → apri il primo
    if (!$corrente) {
        apri_posizione($db, $idDevice, $nuovaPos);
        return;
    }

    // Posizione invariata → nessuna azione
    if ($corrente['posizione'] === $nuovaPos) return;

    // ── DOPPIA ISTERESI ──────────────────────────────────────

    // Recupera tutti i campioni degli ultimi MIN_POSIZIONE_MINUTI minuti
    $recenti = $db->prepare(
        "SELECT x, y, z FROM gir_raw
          WHERE id_device = :id
            AND ricevuto_alle >= DATE_SUB(NOW(), INTERVAL :minuti MINUTE)
          ORDER BY ricevuto_alle DESC"
    );
    $recenti->execute([':id' => $idDevice, ':minuti' => MIN_POSIZIONE_MINUTI]);
    $recenti = $recenti->fetchAll();

    // TODO: se count($recenti) è troppo basso → potenziale alert diagnostico
    // "device invia troppo pochi campioni" — da implementare
    if (empty($recenti)) return;

    // Conta quanti campioni concordano con la nuova posizione
    $conferme = 0;
    foreach ($recenti as $r) {
        if (calcola_posizione((int)$r['x'], (int)$r['y'], (int)$r['z']) === $nuovaPos) {
            $conferme++;
        }
    }

    // Verifica soglia percentuale
    $perc = ($conferme / count($recenti)) * 100;
    if ($perc < SOGLIA_CONFERMA_PERC) return; // troppo rumore → cambio scartato

    // ── CAMBIO CONFERMATO ────────────────────────────────────

    // Chiudi record corrente
    $db->prepare(
        "UPDATE gir_posizione_log
         SET terminato_alle = NOW(),
             durata_minuti  = GREATEST(1, TIMESTAMPDIFF(MINUTE, iniziato_alle, NOW()))
         WHERE id = :id"
    )->execute([':id' => $corrente['id']]);

    // Apri nuovo record
    apri_posizione($db, $idDevice, $nuovaPos);
}

function apri_posizione(PDO $db, int $idDevice, string $posizione): void
{
    $db->prepare(
        "INSERT INTO gir_posizione_log (id_device, posizione, iniziato_alle)
         VALUES (:id, :pos, NOW())"
    )->execute([':id' => $idDevice, ':pos' => $posizione]);
}


// ============================================================
//  ALERT
// ============================================================

function analizza_alert(
    PDO $db,
    int $idDevice,
    string $posizione,
    ?int $batteria,
    int $pulsante,
    int $sogliArancio,
    int $sogliaRosso,
    int $silenzioDa,
    int $silenzioA
): void {

    // ── Pulsante di emergenza ────────────────────────────────
    // Il pulsante SOS non viene mai silenziato — priorità assoluta
    if ($pulsante === 1) {
        apri_alert_se_assente($db, $idDevice, 'PULSANTE', null);
        return;
    }

    // ── Batteria scarica ─────────────────────────────────────
    // La batteria non viene silenziata di notte
    if ($batteria > 0 && $batteria < ALERT_BATT_SOGLIA) {
        apri_alert_se_assente($db, $idDevice, 'BATTERIA', null);
    }

    // ── Silenzio notturno ────────────────────────────────────
    // Durante le ore notturne non vengono generati alert di immobilità
    $ora = (int)date('H');
    $in_silenzio = $silenzioDa > $silenzioA
        ? ($ora >= $silenzioDa || $ora < $silenzioA)   // es. 22→07 (attraversa mezzanotte)
        : ($ora >= $silenzioDa && $ora < $silenzioA);  // es. 01→06 (stesso giorno)

    if ($in_silenzio) {
        // Chiudi eventuali alert immobilità aperti prima del silenzio
        chiudi_alert_immobilita($db, $idDevice);
        return;
    }

    // ── Posizioni a rischio ──────────────────────────────────
    $posizioni_a_rischio = ['SUPINO', 'PRONO', 'SCONOSCIUTO'];

    if (!in_array($posizione, $posizioni_a_rischio)) {
        chiudi_alert_immobilita($db, $idDevice);
        return;
    }

    // La posizione deve essere stabile da almeno MIN_POSIZIONE_MINUTI
    // per evitare alert generati su posizioni non ancora validate
    $log = $db->prepare(
        "SELECT TIMESTAMPDIFF(MINUTE, iniziato_alle, NOW()) AS minuti
         FROM gir_posizione_log
         WHERE id_device = :id
           AND posizione = :posizione
           AND terminato_alle IS NULL
           AND iniziato_alle <= DATE_SUB(NOW(), INTERVAL :min_conf MINUTE)
         ORDER BY iniziato_alle DESC
         LIMIT 1"
    );
    $log->execute([':id' => $idDevice, ':posizione' => $posizione, ':min_conf' => MIN_POSIZIONE_MINUTI]);
    $log = $log->fetch();

    if (!$log) return;
    $minuti = (int)$log['minuti'];

    if ($minuti >= $sogliaRosso) {
        chiudi_alert_tipo($db, $idDevice, 'ARANCIO');
        apri_alert_se_assente($db, $idDevice, 'ROSSO', $minuti);
    } elseif ($minuti >= $sogliArancio) {
        apri_alert_se_assente($db, $idDevice, 'ARANCIO', $minuti);
    }
}

/**
 * Apre un alert solo se non ce n'è già uno aperto dello stesso tipo.
 */
function apri_alert_se_assente(PDO $db, int $idDevice, string $tipo, ?int $minuti): void
{
    $esistente = $db->prepare(
        "SELECT id FROM gir_alert
         WHERE id_device = :id AND tipo = :tipo AND chiuso_alle IS NULL
         LIMIT 1"
    );
    $esistente->execute([':id' => $idDevice, ':tipo' => $tipo]);
    if ($esistente->fetch()) return; // già aperto

    $db->prepare(
        "INSERT INTO gir_alert (id_device, tipo, durata_minuti, aperto_alle)
         VALUES (:id, :tipo, :minuti, NOW())"
    )->execute([':id' => $idDevice, ':tipo' => $tipo, ':minuti' => $minuti]);

    // Recupera label e ubicazione per la notifica
    $info = $db->prepare(
        "SELECT d.label, d.mac, u.area, u.subarea
           FROM gir_device d
      LEFT JOIN gir_ubicazione u ON u.id = d.id_ubicazione
          WHERE d.id = :id LIMIT 1"
    );
    $info->execute([':id' => $idDevice]);
    $info = $info->fetch();

    NotificationService::invia(
        $db,
        $idDevice,
        $tipo,
        $info['label'] ?? $info['mac'] ?? null,
        $info['area']    ?? null,
        $info['subarea'] ?? null
    );
}

/**
 * Chiude tutti gli alert di immobilità aperti (ARANCIO + ROSSO).
 * Chiamata quando il paziente cambia posizione.
 */
function chiudi_alert_immobilita(PDO $db, int $idDevice): void
{
    $db->prepare(
        "UPDATE gir_alert
         SET chiuso_alle = NOW()
         WHERE id_device = :id
           AND tipo IN ('ARANCIO', 'ROSSO')
           AND chiuso_alle IS NULL"
    )->execute([':id' => $idDevice]);
}

/**
 * Chiude un alert di un tipo specifico.
 */
function chiudi_alert_tipo(PDO $db, int $idDevice, string $tipo): void
{
    $db->prepare(
        "UPDATE gir_alert
         SET chiuso_alle = NOW()
         WHERE id_device = :id AND tipo = :tipo AND chiuso_alle IS NULL"
    )->execute([':id' => $idDevice, ':tipo' => $tipo]);
}


// ============================================================
//  BLE DECODE
// ============================================================

function parse_ble_adv(array $bytes): array
{
    $res = ['flags' => null, 'manufacturer' => null, 'ibeacon' => null, 'errors' => []];
    $pos = 0;
    $n   = count($bytes);

    while ($pos < $n) {
        $len = $bytes[$pos++];
        if ($len === 0) break;
        if ($pos + $len > $n) {
            $res['errors'][] = 'truncated';
            break;
        }

        $type     = $bytes[$pos++];
        $valueLen = $len - 1;
        $value    = array_slice($bytes, $pos, $valueLen);
        $pos     += $valueLen;

        if ($type === 0x01 && $valueLen >= 1) {
            $res['flags'] = $value[0];
        }

        if ($type === 0xFF && $valueLen >= 2) {
            $cid = $value[0] | ($value[1] << 8);
            $mfg = array_slice($value, 2);

            $res['manufacturer'] = [
                'company_id' => $cid,
                'data_hex'   => bytes_to_hex($mfg),
                'data_len'   => count($mfg),
            ];

            // iBeacon
            if (count($mfg) >= 23 && $mfg[0] === 0x02 && $mfg[1] === 0x15) {
                $res['ibeacon'] = [
                    'uuid'  => format_uuid(array_slice($mfg, 2, 16)),
                    'major' => ($mfg[18] << 8) | $mfg[19],
                    'minor' => ($mfg[20] << 8) | $mfg[21],
                    'tx'    => to_signed8($mfg[22]),
                ];
            }

            // Payload GIRA
            if ($cid === 0xFFFF && count($mfg) === 8) {
                $g = decode_gira_mfg($mfg);
                if ($g !== null) $res['manufacturer']['gira'] = $g;
            }
        }
    }

    return $res;
}

function decode_gira_mfg(array $b): ?array
{
    if (count($b) !== 8 || $b[0] !== 0x0B) return null;

    return [
        'x'     => int16be($b[1], $b[2]),
        'y'     => int16be($b[3], $b[4]),
        'z'     => int16be($b[5], $b[6]),
        'stato' => $b[7],
        'batteria' => null, // TODO: non disponibile dal gateway HTTP — implementare quando il firmware supporterà Service Data
    ];
}


// ============================================================
//  HELPERS
// ============================================================

function respond(int $code, array $payload): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_mac(string $mac): string
{
    return strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
}

function hex_to_bytes(string $hex): ?array
{
    $hex = strtoupper(trim($hex));
    if ($hex === '' || strlen($hex) % 2 !== 0) return null;
    if (!preg_match('/^[0-9A-F]+$/', $hex)) return null;
    $bytes = [];
    for ($i = 0; $i < strlen($hex); $i += 2) {
        $bytes[] = hexdec(substr($hex, $i, 2));
    }
    return $bytes;
}

function bytes_to_hex(array $bytes): string
{
    return implode('', array_map(fn($b) => sprintf('%02X', $b), $bytes));
}

function int16be(int $hi, int $lo): int
{
    $v = (($hi & 0xFF) << 8) | ($lo & 0xFF);
    return ($v & 0x8000) ? $v - 0x10000 : $v;
}

function to_signed8(int $byte): int
{
    return ($byte & 0x80) ? $byte - 0x100 : $byte;
}

function format_uuid(array $b): string
{
    $h = bytes_to_hex($b);
    return strtolower(substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12));
}
