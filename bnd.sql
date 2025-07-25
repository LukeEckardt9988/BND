-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 25. Jul 2025 um 12:24
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `bnd`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `emails`
--

CREATE TABLE `emails` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `sent_at` datetime NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_phishing_copy` tinyint(1) DEFAULT 0,
  `phishing_analysis_data_copy` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `emails`
--

INSERT INTO `emails` (`id`, `template_id`, `recipient_id`, `sent_at`, `is_read`, `is_phishing_copy`, `phishing_analysis_data_copy`) VALUES
(57, 1, 4, '2025-07-25 11:46:20', 1, 0, NULL),
(58, 6, 4, '2025-07-25 11:46:29', 1, 0, NULL),
(59, 5, 4, '2025-07-25 11:47:20', 1, 0, NULL),
(60, 3, 4, '2025-07-25 11:47:34', 1, 1, '{\"ip_address\":\"91.241.72.126\",\"domain\":\"mail21-126.srv2.de\",\"country\":\"RU\"}');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `is_phishing` tinyint(1) NOT NULL DEFAULT 0,
  `phishing_analysis_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `email_templates`
--

INSERT INTO `email_templates` (`id`, `sender_name`, `sender_email`, `subject`, `body_html`, `is_phishing`, `phishing_analysis_data`) VALUES
(1, 'BND Ausbildungsleitung', 'ausbildung@bnd.de', 'Willkommen, Rekrut!', 'Sehr geehrter Rekrut,<br><br>willkommen bei Ihrer Ausbildung. Ihre erste Aufgabe besteht darin, sich mit der Konsole vertraut zu machen. Geben Sie \'<b>start training</b>\' ein, um Ihre erste Mission zu erhalten.<br><br>Viel Erfolg,<br>Ihre Ausbildungsleitung', 0, NULL),
(2, 'BND Missionsleitung', 'mission@bnd.de', 'Anweisung: Netzwerk-Scan', 'Rekrut,<br><br>gute Arbeit. Der erste Scan hat ein unbekanntes Gerät im Netzwerk aufgedeckt (<b>10.0.10.13</b>).<br><br>Ihre nächste Aufgabe: Führen Sie einen aggressiven Scan auf dieses Gerät durch, um offene Ports, die laufenden Dienste und das Betriebssystem zu identifizieren. Nutzen Sie den Befehl \'<b>nmap -A [IP-Adresse]</b>\'.<br><br>Missionsleitung', 0, NULL),
(3, 'Bundeskanzleramt', 'kanzler@bundeskanzleramt.de', 'DRINGEND: Wichtige Sicherheitsdirektive', 'Sehr geehrte/r Mitarbeiter/in,<br><br>im Rahmen der aktuellen Sicherheitslage erhalten Sie hiermit eine streng geheime Direktive.<br><br>Es ist absolut entscheidend, dass Sie das beigefügte Dokument umgehend herunterladen: <a href=\"#\" onclick=\"alert(\'PHISHING-VERSUCH ERKANNT!\'); return false;\">STRENG_GEHEIM.pdf</a><br><br>Ihre Kooperation ist für die Sicherheit des Landes von höchster Bedeutung.<br><br>Im Auftrag des Bundeskanzlers', 1, '{\"ip_address\":\"91.241.72.126\",\"domain\":\"mail21-126.srv2.de\",\"country\":\"RU\"}'),
(4, 'BND Missionsleitung', 'mission@bnd.de', 'Anweisung: Bedrohung neutralisieren', 'Rekrut,<br><br>Der Scan war erfolgreich. Sie haben einen Trojaner auf Port <b>1337</b> identifiziert. Sie haben nun die Freigabe für das Firewall-Tool \'ufw\' erhalten.<br><br>Ihre Aufgabe: Blockieren Sie den schädlichen Port umgehend. Der Befehl lautet: \'<b>sudo ufw deny [Portnummer]</b>\'.<br><br>Missionsleitung', 0, NULL),
(5, 'BND Missionsleitung', 'mission@bnd.de', 'Missionsbericht: Bedrohung Neutralisiert', 'Gute Arbeit, Rekrut. Sie haben die unmittelbare Bedrohung im Netzwerk erfolgreich neutralisiert und damit Level 1 abgeschlossen. Bleiben Sie wachsam, neue Herausforderungen warten bereits.', 0, NULL),
(6, 'BND Ausbildungs-Bot', 'bot@bnd.de', 'Einführung: Nmap-Tool', 'Willkommen zur Ihrer ersten praktischen Übung. Das <b>Network Mapper (Nmap)</b> Tool ist fundamental für Ihre Arbeit. Machen Sie sich mit den Grundlagen vertraut, indem Sie das interne Handbuch im Browser aufrufen: <a href=\"#\" onclick=\"openInGameBrowser(\'browser.php?page=nmap.html\'); return false;\">Nmap Handbuch öffnen</a>', 0, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `missions`
--

CREATE TABLE `missions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `missions`
--

INSERT INTO `missions` (`id`, `name`, `description`) VALUES
(1, 'Level 1: Netzwerk-Grundlagen', 'Einführung in die Netzwerkanalyse und -sicherung.'),
(2, 'Level 2: Phishing-Analyse', 'Erkennen und Melden von Phishing-Angriffen.');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mission_progress`
--

CREATE TABLE `mission_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mission_id` int(11) NOT NULL,
  `current_step` int(11) NOT NULL DEFAULT 1,
  `status` enum('inactive','active','completed') NOT NULL DEFAULT 'inactive',
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `mission_progress`
--

INSERT INTO `mission_progress` (`id`, `user_id`, `mission_id`, `current_step`, `status`, `is_completed`, `updated_at`) VALUES
(24, 4, 1, 4, 'completed', 0, '2025-07-25 09:47:34');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mission_steps`
--

CREATE TABLE `mission_steps` (
  `id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `description` text NOT NULL,
  `next_step_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `player_devices`
--

CREATE TABLE `player_devices` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `hostname` varchar(100) DEFAULT 'default-pc',
  `os_type` varchar(50) DEFAULT 'Linux',
  `gateway_ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{"80":{"status":"closed"},"443":{"status":"closed"},"22":{"status":"closed"}}',
  `firewall_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `installed_software` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `event_log` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `player_devices`
--

INSERT INTO `player_devices` (`id`, `user_id`, `hostname`, `os_type`, `gateway_ip`, `user_agent`, `ports`, `firewall_rules`, `installed_software`, `event_log`) VALUES
(1, 4, 'luki-pc', 'Linux', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0', '{\"22\":{\"status\":\"closed\"}, \"80\":{\"status\":\"closed\"}}', '[]', '[]', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `programs`
--

CREATE TABLE `programs` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `is_initial` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `programs`
--

INSERT INTO `programs` (`id`, `name`, `description`, `is_initial`) VALUES
(1, 'nmap', 'Network Mapper - Tool zur Erkundung und Analyse von Netzwerken.', 1),
(2, 'ufw', 'Uncomplicated Firewall - Tool zur Verwaltung der Firewall.', 0),
(3, 'analyze_email_header', 'Analysiert den Header einer E-Mail.', 0),
(4, 'whois', 'Zeigt Informationen zu einer IP-Adresse oder Domain.', 0),
(5, 'report_phishing_ip', 'Meldet eine IP-Adresse als Phishing-Quelle.', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `ip_address`, `device_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$T.P8A.u5L43fLd5sC6v8A.uPq9.L1s2j5H/uA3.F/f.E/d.G/h.I', 'admin@bnd.de', '10.0.10.254', NULL, '2025-07-24 19:20:58'),
(4, 'Luki', '$2y$10$.5PzE3sm6TLv7/S1G5wfAuxaUyqITPKIVd7LG1CbHkHKPKdljqYwO', 'luki@bnd.de', '10.0.10.2', 1, '2025-07-24 19:26:30');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(512) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indizes für die Tabelle `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `mission_progress`
--
ALTER TABLE `mission_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `mission_steps`
--
ALTER TABLE `mission_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mission_step` (`mission_id`,`step_number`);

--
-- Indizes für die Tabelle `player_devices`
--
ALTER TABLE `player_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Indizes für die Tabelle `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT für Tabelle `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `mission_progress`
--
ALTER TABLE `mission_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT für Tabelle `mission_steps`
--
ALTER TABLE `mission_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `player_devices`
--
ALTER TABLE `player_devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `emails`
--
ALTER TABLE `emails`
  ADD CONSTRAINT `emails_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emails_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `mission_progress`
--
ALTER TABLE `mission_progress`
  ADD CONSTRAINT `mission_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `player_devices`
--
ALTER TABLE `player_devices`
  ADD CONSTRAINT `player_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `fk_user_favorites_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
