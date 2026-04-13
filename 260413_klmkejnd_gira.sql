-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Apr 13, 2026 alle 17:37
-- Versione del server: 10.6.24-MariaDB-cll-lve-log
-- Versione PHP: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `klmkejnd_gira`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_alert`
--

CREATE TABLE `gir_alert` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_device` int(10) UNSIGNED NOT NULL,
  `tipo` enum('ARANCIO','ROSSO','BATTERIA','OFFLINE','PULSANTE') NOT NULL,
  `durata_minuti` smallint(5) UNSIGNED DEFAULT NULL,
  `aperto_alle` timestamp NOT NULL DEFAULT current_timestamp(),
  `chiuso_alle` timestamp NULL DEFAULT NULL,
  `gestito` tinyint(1) NOT NULL DEFAULT 0,
  `id_utente_gestore` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_device`
--

CREATE TABLE `gir_device` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_struttura` int(10) UNSIGNED NOT NULL,
  `id_ubicazione` int(10) UNSIGNED DEFAULT NULL,
  `mac` varchar(17) NOT NULL,
  `label` varchar(60) DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `creato_il` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_device_stato`
--

CREATE TABLE `gir_device_stato` (
  `id_device` int(10) UNSIGNED NOT NULL,
  `posizione` enum('SUPINO','LATO_A','LATO_B','PRONO','SEDUTO','SCONOSCIUTO') NOT NULL DEFAULT 'SCONOSCIUTO',
  `stato_batt` tinyint(3) UNSIGNED DEFAULT NULL,
  `stato_segnale` smallint(6) DEFAULT NULL,
  `stato_pulsante` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_contatto` timestamp NOT NULL DEFAULT current_timestamp(),
  `aggiornato_alle` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_notifica_preferenze`
--

CREATE TABLE `gir_notifica_preferenze` (
  `id_utente` int(10) UNSIGNED NOT NULL,
  `push_attiva` tinyint(1) NOT NULL DEFAULT 1,
  `mail_attiva` tinyint(1) NOT NULL DEFAULT 0,
  `mail_istantanea` tinyint(1) NOT NULL DEFAULT 0,
  `mail_riepilogo` tinyint(1) NOT NULL DEFAULT 0,
  `alert_arancio` tinyint(1) NOT NULL DEFAULT 1,
  `alert_rosso` tinyint(1) NOT NULL DEFAULT 1,
  `alert_batteria` tinyint(1) NOT NULL DEFAULT 1,
  `alert_offline` tinyint(1) NOT NULL DEFAULT 1,
  `ora_riepilogo` tinyint(3) UNSIGNED DEFAULT 7
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_posizione_log`
--

CREATE TABLE `gir_posizione_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_device` int(10) UNSIGNED NOT NULL,
  `posizione` enum('SUPINO','LATO_A','LATO_B','PRONO','SEDUTO','SCONOSCIUTO') NOT NULL,
  `iniziato_alle` timestamp NOT NULL DEFAULT current_timestamp(),
  `terminato_alle` timestamp NULL DEFAULT NULL,
  `durata_minuti` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_push_subscription`
--

CREATE TABLE `gir_push_subscription` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` text NOT NULL,
  `auth` text NOT NULL,
  `creata_il` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_raw`
--

CREATE TABLE `gir_raw` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_device` int(10) UNSIGNED NOT NULL,
  `x` decimal(8,4) NOT NULL,
  `y` decimal(8,4) NOT NULL,
  `z` decimal(8,4) NOT NULL,
  `ricevuto_alle` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_remember_tokens`
--

CREATE TABLE `gir_remember_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `scadenza` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_ruolo`
--

CREATE TABLE `gir_ruolo` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nome` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `gir_ruolo`
--

INSERT INTO `gir_ruolo` (`id`, `nome`) VALUES
(2, 'admin'),
(4, 'medico'),
(1, 'superadmin'),
(3, 'utente');

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_soglie`
--

CREATE TABLE `gir_soglie` (
  `id_struttura` int(10) UNSIGNED NOT NULL,
  `soglia_arancio_min` smallint(5) UNSIGNED NOT NULL DEFAULT 20,
  `soglia_rosso_min` smallint(5) UNSIGNED NOT NULL DEFAULT 45,
  `campioni_conferma` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `aggiornato_alle` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_struttura`
--

CREATE TABLE `gir_struttura` (
  `id` int(10) UNSIGNED NOT NULL,
  `ragione_sociale` varchar(120) NOT NULL,
  `partita_iva` varchar(11) NOT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `attiva` tinyint(1) NOT NULL DEFAULT 1,
  `creata_il` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_subscription`
--

CREATE TABLE `gir_subscription` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_struttura` int(10) UNSIGNED NOT NULL,
  `piano` enum('FREE','BASIC','PRO','ENTERPRISE') NOT NULL DEFAULT 'FREE',
  `stato` enum('ATTIVA','SOSPESA','SCADUTA','CANCELLATA') NOT NULL DEFAULT 'ATTIVA',
  `inizio_il` date NOT NULL,
  `fine_il` date DEFAULT NULL,
  `max_device` smallint(5) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `creata_il` timestamp NOT NULL DEFAULT current_timestamp(),
  `aggiornata_il` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_ubicazione`
--

CREATE TABLE `gir_ubicazione` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_struttura` int(10) UNSIGNED NOT NULL,
  `area` varchar(60) NOT NULL COMMENT 'Es: 1° piano',
  `subarea` varchar(255) DEFAULT NULL COMMENT 'Es: Stanza 8'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_utente_device`
--

CREATE TABLE `gir_utente_device` (
  `id_utente` int(10) UNSIGNED NOT NULL,
  `id_device` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_utente_struttura`
--

CREATE TABLE `gir_utente_struttura` (
  `id_utente` int(10) UNSIGNED NOT NULL,
  `id_struttura` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `gir_utenti`
--

CREATE TABLE `gir_utenti` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_ruolo` tinyint(3) UNSIGNED NOT NULL,
  `nome` varchar(60) NOT NULL,
  `cognome` varchar(60) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `mail` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `deve_cambiare_pwd` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_accesso` timestamp NULL DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `creato_il` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `gir_utenti`
--

INSERT INTO `gir_utenti` (`id`, `id_ruolo`, `nome`, `cognome`, `telefono`, `mail`, `password_hash`, `deve_cambiare_pwd`, `ultimo_accesso`, `attivo`, `creato_il`) VALUES
(1, 1, 'Piero', 'tiSchedo', NULL, 'piero@tischedo.it', '$2y$10$S4lc6OKDDIRYQsvDJxy/IOxKZzVCwnNYcc7yBUic7oLpV8lvDsv3W', 0, NULL, 1, '2026-04-13 15:11:59');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `gir_alert`
--
ALTER TABLE `gir_alert`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_device` (`id_device`),
  ADD KEY `idx_alert_aperto` (`aperto_alle`),
  ADD KEY `idx_alert_gestito` (`gestito`),
  ADD KEY `fk_alert_gestore` (`id_utente_gestore`);

--
-- Indici per le tabelle `gir_device`
--
ALTER TABLE `gir_device`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mac` (`mac`),
  ADD KEY `fk_dev_struttura` (`id_struttura`),
  ADD KEY `fk_dev_ubicazione` (`id_ubicazione`);

--
-- Indici per le tabelle `gir_device_stato`
--
ALTER TABLE `gir_device_stato`
  ADD PRIMARY KEY (`id_device`);

--
-- Indici per le tabelle `gir_notifica_preferenze`
--
ALTER TABLE `gir_notifica_preferenze`
  ADD PRIMARY KEY (`id_utente`);

--
-- Indici per le tabelle `gir_posizione_log`
--
ALTER TABLE `gir_posizione_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_inizio` (`id_device`,`iniziato_alle`);

--
-- Indici per le tabelle `gir_push_subscription`
--
ALTER TABLE `gir_push_subscription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_push_utente` (`id_utente`);

--
-- Indici per le tabelle `gir_raw`
--
ALTER TABLE `gir_raw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_raw_device_ts` (`id_device`,`ricevuto_alle`);

--
-- Indici per le tabelle `gir_remember_tokens`
--
ALTER TABLE `gir_remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `fk_rt_utente` (`id_utente`);

--
-- Indici per le tabelle `gir_ruolo`
--
ALTER TABLE `gir_ruolo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nome` (`nome`);

--
-- Indici per le tabelle `gir_soglie`
--
ALTER TABLE `gir_soglie`
  ADD PRIMARY KEY (`id_struttura`);

--
-- Indici per le tabelle `gir_struttura`
--
ALTER TABLE `gir_struttura`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_piva` (`partita_iva`);

--
-- Indici per le tabelle `gir_subscription`
--
ALTER TABLE `gir_subscription`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subscription_struttura` (`id_struttura`);

--
-- Indici per le tabelle `gir_ubicazione`
--
ALTER TABLE `gir_ubicazione`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pos_struttura` (`id_struttura`);

--
-- Indici per le tabelle `gir_utente_device`
--
ALTER TABLE `gir_utente_device`
  ADD PRIMARY KEY (`id_utente`,`id_device`),
  ADD KEY `fk_ud_device` (`id_device`);

--
-- Indici per le tabelle `gir_utente_struttura`
--
ALTER TABLE `gir_utente_struttura`
  ADD PRIMARY KEY (`id_utente`,`id_struttura`),
  ADD KEY `fk_us_struttura` (`id_struttura`);

--
-- Indici per le tabelle `gir_utenti`
--
ALTER TABLE `gir_utenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mail` (`mail`),
  ADD KEY `fk_utenti_ruolo` (`id_ruolo`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `gir_alert`
--
ALTER TABLE `gir_alert`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_device`
--
ALTER TABLE `gir_device`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_posizione_log`
--
ALTER TABLE `gir_posizione_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_push_subscription`
--
ALTER TABLE `gir_push_subscription`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_raw`
--
ALTER TABLE `gir_raw`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_remember_tokens`
--
ALTER TABLE `gir_remember_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_ruolo`
--
ALTER TABLE `gir_ruolo`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `gir_struttura`
--
ALTER TABLE `gir_struttura`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_subscription`
--
ALTER TABLE `gir_subscription`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_ubicazione`
--
ALTER TABLE `gir_ubicazione`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gir_utenti`
--
ALTER TABLE `gir_utenti`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `gir_alert`
--
ALTER TABLE `gir_alert`
  ADD CONSTRAINT `fk_alert_device` FOREIGN KEY (`id_device`) REFERENCES `gir_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alert_gestore` FOREIGN KEY (`id_utente_gestore`) REFERENCES `gir_utenti` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_device`
--
ALTER TABLE `gir_device`
  ADD CONSTRAINT `fk_dev_struttura` FOREIGN KEY (`id_struttura`) REFERENCES `gir_struttura` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dev_ubicazione` FOREIGN KEY (`id_ubicazione`) REFERENCES `gir_ubicazione` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_device_stato`
--
ALTER TABLE `gir_device_stato`
  ADD CONSTRAINT `fk_stato_device` FOREIGN KEY (`id_device`) REFERENCES `gir_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_notifica_preferenze`
--
ALTER TABLE `gir_notifica_preferenze`
  ADD CONSTRAINT `fk_np_utente` FOREIGN KEY (`id_utente`) REFERENCES `gir_utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `gir_posizione_log`
--
ALTER TABLE `gir_posizione_log`
  ADD CONSTRAINT `fk_log_device` FOREIGN KEY (`id_device`) REFERENCES `gir_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_push_subscription`
--
ALTER TABLE `gir_push_subscription`
  ADD CONSTRAINT `fk_push_utente` FOREIGN KEY (`id_utente`) REFERENCES `gir_utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `gir_raw`
--
ALTER TABLE `gir_raw`
  ADD CONSTRAINT `fk_raw_device` FOREIGN KEY (`id_device`) REFERENCES `gir_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_remember_tokens`
--
ALTER TABLE `gir_remember_tokens`
  ADD CONSTRAINT `fk_rt_utente` FOREIGN KEY (`id_utente`) REFERENCES `gir_utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `gir_soglie`
--
ALTER TABLE `gir_soglie`
  ADD CONSTRAINT `fk_soglie_struttura` FOREIGN KEY (`id_struttura`) REFERENCES `gir_struttura` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_subscription`
--
ALTER TABLE `gir_subscription`
  ADD CONSTRAINT `fk_subscription_struttura` FOREIGN KEY (`id_struttura`) REFERENCES `gir_struttura` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_ubicazione`
--
ALTER TABLE `gir_ubicazione`
  ADD CONSTRAINT `fk_pos_struttura` FOREIGN KEY (`id_struttura`) REFERENCES `gir_struttura` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_utente_device`
--
ALTER TABLE `gir_utente_device`
  ADD CONSTRAINT `fk_ud_device` FOREIGN KEY (`id_device`) REFERENCES `gir_device` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ud_utente` FOREIGN KEY (`id_utente`) REFERENCES `gir_utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `gir_utente_struttura`
--
ALTER TABLE `gir_utente_struttura`
  ADD CONSTRAINT `fk_us_struttura` FOREIGN KEY (`id_struttura`) REFERENCES `gir_struttura` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_us_utente` FOREIGN KEY (`id_utente`) REFERENCES `gir_utenti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `gir_utenti`
--
ALTER TABLE `gir_utenti`
  ADD CONSTRAINT `fk_utenti_ruolo` FOREIGN KEY (`id_ruolo`) REFERENCES `gir_ruolo` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
