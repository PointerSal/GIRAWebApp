<?php

/**
 * ============================================================
 *  GIRA · Care Monitor SaaS
 *  Architettura: MVC leggero senza framework esterno
 * ============================================================
 *
 *  /home/klmkejnd/SaaS/gira/          ← fuori dalla webroot
 *  ├── app/
 *  │   ├── Config/
 *  │   │   └── config.php             ← costanti globali (DB, path, ruoli)
 *  │   │
 *  │   ├── Core/
 *  │   │   ├── Database.php           ← singleton PDO
 *  │   │   ├── Router.php             ← dispatcher (opzionale, logica in index)
 *  │   │   ├── Controller.php         ← base controller (render, redirect, json)
 *  │   │   ├── Model.php              ← base model (CRUD generico)
 *  │   │   ├── Auth.php               ← sessione, login, ruoli, remember token
 *  │   │   ├── Middleware.php         ← controllo permessi pre-controller
 *  │   │   └── Mailer.php             ← PHPMailer wrapper (notifiche mail)
 *  │   │
 *  │   ├── Models/
 *  │   │   ├── Struttura.php          ← RSA
 *  │   │   ├── Utente.php             ← utenti + ruoli
 *  │   │   ├── Device.php             ← sensori giroscopici
 *  │   │   ├── Ubicazione.php         ← aree/subaree fisiche (ex gir_posizione)
 *  │   │   ├── Alert.php              ← alert generati
 *  │   │   ├── PushSubscription.php   ← token PWA
 *  │   │   └── Subscription.php       ← piani commerciali strutture
 *  │   │
 *  │   ├── Controllers/
 *  │   │   ├── AuthController.php     ← login / logout / profilo / password
 *  │   │   ├── DashboardController.php← dashboard per ruolo
 *  │   │   ├── StrutturaController.php← CRUD strutture RSA (superadmin)
 *  │   │   ├── UtenteController.php   ← CRUD utenti (superadmin + admin)
 *  │   │   ├── DeviceController.php   ← CRUD device + assegnazione ubicazione
 *  │   │   ├── UbicazioneController.php← CRUD aree/subaree
 *  │   │   ├── AlertController.php    ← lista alert, presa in carico, storico
 *  │   │   ├── IngestController.php   ← riceve HTTP POST dal gateway
 *  │   │   ├── PushController.php     ← registra subscription PWA
 *  │   │   └── ReportController.php   ← report e statistiche
 *  │   │
 *  │   └── Views/
 *  │       ├── layout/
 *  │       │   ├── header.php
 *  │       │   ├── footer.php
 *  │       │   ├── sidebar.php
 *  │       │   └── 404.php
 *  │       ├── auth/
 *  │       │   ├── login.php
 *  │       │   └── profilo.php
 *  │       ├── dashboard/
 *  │       │   ├── superadmin.php
 *  │       │   ├── admin.php
 *  │       │   └── operatore.php
 *  │       ├── strutture/
 *  │       │   ├── index.php
 *  │       │   ├── form.php
 *  │       │   └── show.php
 *  │       ├── utenti/
 *  │       │   ├── index.php
 *  │       │   ├── form.php
 *  │       │   └── preferenze.php     ← notifiche push/mail
 *  │       ├── device/
 *  │       │   ├── index.php
 *  │       │   ├── form.php
 *  │       │   └── show.php           ← dettaglio device + storico posizioni paziente
 *  │       ├── ubicazioni/
 *  │       │   ├── index.php
 *  │       │   └── form.php
 *  │       ├── alert/
 *  │       │   ├── index.php          ← lista alert attivi
 *  │       │   └── storico.php        ← alert chiusi/gestiti
 *  │       └── report/
 *  │           └── index.php
 *  │
 *  └── vendor/                        ← Composer (PHPMailer, web-push-php)
 *
 *  /home/klmkejnd/tis.gira/           ← webroot pubblica
 *  ├── index.php                      ← questo file
 *  ├── .htaccess
 *  ├── manifest.json                  ← PWA manifest
 *  ├── sw.js                          ← Service Worker (push notification)
 *  └── assets/
 *      ├── css/
 *      │   └── gira.css
 *      ├── js/
 *      │   └── push.js                ← registra subscription PWA
 *      └── img/
 *          └── icon-192.png           ← icona PWA
 *
 * ============================================================ */


// ============================================================
//  SESSIONE
// ============================================================
ini_set('session.gc_maxlifetime', 60 * 60 * 2); // 2 ore
session_set_cookie_params(60 * 60 * 2);
session_start();


// ============================================================
//  CARICAMENTO CORE
// ============================================================
require_once '/home/klmkejnd/SaaS/gira/app/Config/config.php'; // ← path assoluto, poi tutto via BASE_PATH
require_once BASE_PATH . 'app/Core/Database.php';
require_once BASE_PATH . 'app/Core/Auth.php';
require_once BASE_PATH . 'app/Core/Middleware.php';

// Mailer — solo se Composer è installato
//if (file_exists(VENDOR_PATH . 'autoload.php')) {
//    require_once VENDOR_PATH . 'autoload.php';
//    require_once BASE_PATH . 'app/Core/Mailer.php';
//}


/* // ============================================================
//  CARICAMENTO MODELS
// ============================================================
require_once BASE_PATH . 'app/Models/Struttura.php';
require_once BASE_PATH . 'app/Models/Utente.php';
require_once BASE_PATH . 'app/Models/Device.php';
require_once BASE_PATH . 'app/Models/Ubicazione.php';
require_once BASE_PATH . 'app/Models/Alert.php';
require_once BASE_PATH . 'app/Models/PushSubscription.php';
require_once BASE_PATH . 'app/Models/Subscription.php';
*/

// ============================================================
//  CARICAMENTO CONTROLLERS
// ============================================================
require_once BASE_PATH . 'app/Controllers/AuthController.php';
require_once BASE_PATH . 'app/Controllers/DashboardController.php';
require_once BASE_PATH . 'app/Controllers/StrutturaController.php';
require_once BASE_PATH . 'app/Controllers/UtenteController.php';
require_once BASE_PATH . 'app/Controllers/DeviceController.php';
require_once BASE_PATH . 'app/Controllers/UbicazioneController.php';
require_once BASE_PATH . 'app/Controllers/AlertController.php';
// require_once BASE_PATH . 'app/Controllers/IngestController.php';
// require_once BASE_PATH . 'app/Controllers/PushController.php';
// require_once BASE_PATH . 'app/Controllers/ReportController.php';  quando decommenti qua, decommenta anche righe 283 - 286


// ============================================================
//  REMEMBER TOKEN — riautentica se sessione scaduta
// ============================================================
Auth::controlla_remember_token();


// ============================================================
//  ROUTER
//  URL struttura: /gira/CONTROLLER/AZIONE/ID
//  Esempio:       /gira/device/show/42
// ============================================================
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = preg_replace('#^/gira#', '', $request);
$request = trim($request, '/');
$segmenti = $request !== '' ? explode('/', $request) : [];

$controller = $segmenti[0] ?? 'dashboard';
$azione     = $segmenti[1] ?? 'index';
$id         = isset($segmenti[2]) ? (int)$segmenti[2] : null;


// ============================================================
//  ROUTING TABLE
// ============================================================
$routes = [

    // ----------------------------------------------------------
    // AUTH
    // ----------------------------------------------------------
    'auth' => [
        'login'           => fn() => AuthController::login(),
        'login-post'      => fn() => AuthController::loginPost(),
        'logout'          => fn() => AuthController::logout(),
        'cambia-password' => fn() => AuthController::cambiaPassword(),
        'cambia-password-post' => fn() => AuthController::cambiaPasswordPost(),
        'profilo'         => fn() => AuthController::profilo(),
        'profilo-post'    => fn() => AuthController::profiloPost(),
    ],

    // ----------------------------------------------------------
    // DASHBOARD (per ruolo)
    // ----------------------------------------------------------
    'dashboard' => [
        'index' => fn() => DashboardController::index(),
    ],

    // ----------------------------------------------------------
    // STRUTTURE RSA (superadmin)
    // ----------------------------------------------------------
    'strutture' => [
        'index'        => fn() => StrutturaController::index(),
        'crea'         => fn() => StrutturaController::crea(),
        'crea-post'    => fn() => StrutturaController::creaPost(),
        'modifica'     => fn() => StrutturaController::modifica($id),
        'modifica-post' => fn() => StrutturaController::modificaPost(),
        'elimina'      => fn() => StrutturaController::elimina($id),
        'show'         => fn() => StrutturaController::show($id),
        'attiva'       => fn() => StrutturaController::attiva($id),
        'sospendi'     => fn() => StrutturaController::sospendi($id),
    ],

    'struttura-attiva' => [
        'set' => fn() => (function () {
            $id = (int)($_POST['id_struttura'] ?? 0);
            Auth::set_struttura_attiva($id);
            $redirect = $_POST['redirect'] ?? '/dashboard';
            header('Location: ' . APP_URL . $redirect);
            exit;
        })(),
    ],

    // ----------------------------------------------------------
    // UTENTI (superadmin + admin)
    // ----------------------------------------------------------
    'utenti' => [
        'index'              => fn() => UtenteController::index(),
        'crea'               => fn() => UtenteController::crea(),
        'crea-post'          => fn() => UtenteController::creaPost(),
        'modifica'           => fn() => UtenteController::modifica($id),
        'modifica-post'      => fn() => UtenteController::modificaPost(),
        'elimina'            => fn() => UtenteController::elimina($id),
        'reset-pwd'          => fn() => UtenteController::resetPassword($id),
        'reset-pwd-post'     => fn() => UtenteController::resetPasswordPost(),
        'preferenze'         => fn() => UtenteController::preferenze($id),
        'preferenze-post'    => fn() => UtenteController::preferenzePost(),
        'device-assegnati'   => fn() => UtenteController::deviceAssegnati($id),
        'device-assegnati-post' => fn() => UtenteController::deviceAssegnatiPost(),
    ],

    // ----------------------------------------------------------
    // DEVICE (superadmin + admin)
    // ----------------------------------------------------------
    'device' => [
        'index'        => fn() => DeviceController::index(),
        'show'         => fn() => DeviceController::show($id),
        'crea'         => fn() => DeviceController::crea(),
        'crea-post'    => fn() => DeviceController::creaPost(),
        'modifica'     => fn() => DeviceController::modifica($id),
        'modifica-post' => fn() => DeviceController::modificaPost(),
        'elimina'      => fn() => DeviceController::elimina($id),
        'assegna'      => fn() => DeviceController::assegna($id),
        'assegna-post' => fn() => DeviceController::assegnaPost(),
        'ubicazioni-json' => fn() => DeviceController::ubicazioniJson(),
    ],

    // ----------------------------------------------------------
    // UBICAZIONI — aree e subaree fisiche della struttura (admin)
    // ----------------------------------------------------------
    'ubicazioni' => [
        'index'        => fn() => UbicazioneController::index(),
        'crea'         => fn() => UbicazioneController::crea(),
        'crea-post'    => fn() => UbicazioneController::creaPost(),
        'modifica'     => fn() => UbicazioneController::modifica($id),
        'modifica-post' => fn() => UbicazioneController::modificaPost(),
        'elimina'      => fn() => UbicazioneController::elimina($id),
    ],

    // ----------------------------------------------------------
    // ALERT (admin + operatore)
    // ----------------------------------------------------------
    'alert' => [
        'index'          => fn() => AlertController::index(),
        'storico'        => fn() => AlertController::storico(),
        'prendi-in-carico' => fn() => AlertController::prendiInCarico($id),
        'chiudi'         => fn() => AlertController::chiudi($id),
        'chiudi-post'    => fn() => AlertController::chiudiPost(),
    ],

    // ----------------------------------------------------------
    // INGEST — riceve i pacchetti HTTP POST dal gateway
    // NON richiede autenticazione utente — usa API key
    // ----------------------------------------------------------
    'ingest' => [
        'index' => fn() => IngestController::index(),
    ],

    // ----------------------------------------------------------
    // PUSH — gestione subscription PWA
    // ----------------------------------------------------------
    'push' => [
        'subscribe'   => fn() => PushController::subscribe(),
        'unsubscribe' => fn() => PushController::unsubscribe(),
    ],

    // ----------------------------------------------------------
    // REPORT (admin + superadmin)
    // ----------------------------------------------------------
    /*     'report' => [
        'index'   => fn() => ReportController::index(),
        'esporta' => fn() => ReportController::esporta(),
    ], */

];


// ============================================================
//  GESTIONE ROOT — landing page o dashboard
// ============================================================
if ($controller === 'dashboard' && $azione === 'index') {
    if (!Auth::isLogged()) {
        include BASE_PATH . 'app/Views/landing.php';
        exit;
    }
    DashboardController::index();
    exit;
}


// ============================================================
//  DISPATCH
// ============================================================
if (isset($routes[$controller][$azione])) {
    call_user_func($routes[$controller][$azione]);
} else {
    http_response_code(404);
    include BASE_PATH . 'app/Views/layout/404.php';
}
