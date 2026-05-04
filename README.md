# GIRA · Care Monitor SaaS

### Architettura: MVC leggero senza framework esterno

---

## 📂 Struttura del Progetto

```text
/home/klmkejnd/SaaS/gira/          <-- 🔒 Fuori dalla webroot (Logica)
├── app/
│   ├── Config/
│   │   └── config.php             <-- Costanti globali (DB, path, ruoli)
│   │
│   ├── Core/
│   │   ├── Database.php           <-- Singleton PDO
│   │   ├── Router.php             <-- Dispatcher
│   │   ├── Controller.php         <-- Base controller (render, redirect, json)
│   │   ├── Model.php              <-- Base model (CRUD generico)
│   │   ├── Auth.php               <-- Sessione, login, ruoli, remember token
│   │   ├── Middleware.php         <-- Controllo permessi pre-controller
│   │   └── Mailer.php             <-- PHPMailer wrapper (notifiche mail)
│   │
│   ├── Models/
│   │   ├── Struttura.php          <-- RSA
│   │   ├── Utente.php             <-- Utenti + ruoli
│   │   ├── Device.php             <-- Sensori giroscopici
│   │   ├── Ubicazione.php         <-- Aree/subaree fisiche (ex gir_posizione)
│   │   ├── Alert.php              <-- Alert generati
│   │   ├── PushSubscription.php   <-- Token PWA
│   │   └── Subscription.php       <-- Piani commerciali strutture
│   │
│   ├── Controllers/
│   │   ├── AuthController.php      
│   │   ├── DashboardController.php 
│   │   ├── StrutturaController.php 
│   │   ├── UtenteController.php    
│   │   ├── DeviceController.php    
│   │   ├── UbicazioneController.php
│   │   ├── AlertController.php     
│   │   ├── IngestController.php    <-- Riceve HTTP POST dal gateway
│   │   ├── PushController.php      <-- Registra subscription PWA
│   │   └── ReportController.php    
│   │
│   └── Views/
│       ├── layout/                <-- header, footer, sidebar, 404
│       ├── auth/                  <-- login, profilo
│       ├── dashboard/             <-- superadmin, admin, operatore
│       ├── strutture/             
│       ├── utenti/                <-- index, form, preferenze (push/mail)
│       ├── device/                <-- index, form, show (storico posizioni)
│       ├── ubicazioni/            
│       ├── alert/                 <-- index (attivi), storico (chiusi)
│       └── report/                
│
└── vendor/                        <-- Composer (PHPMailer, web-push-php)

/home/klmkejnd/tis.gira/           <-- 🌍 Webroot pubblica (Accessibile)
├── index.php                      <-- Front Controller
├── .htaccess                      <-- URL Rewriting
├── manifest.json                  <-- PWA manifest
├── sw.js                          <-- Service Worker (Push notifications)
└── assets/
    ├── css/
    │   └── gira.css
    ├── js/
    │   └── push.js                <-- Registra subscription PWA
    └── img/
        └── icon-192.png           <-- Icona PWA
