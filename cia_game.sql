-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 23. Jul 2025 um 07:25
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
-- Datenbank: `cia_game`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `achievements`
--

CREATE TABLE `achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(100) DEFAULT 'default_badge.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `achievements`
--

INSERT INTO `achievements` (`id`, `name`, `description`, `icon`) VALUES
(1, 'Netzwerk-Pionier', 'Du hast deinen ersten Netzwerk-Scan erfolgreich durchgeführt.', 'badge_scan.png');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `commands`
--

CREATE TABLE `commands` (
  `id` int(10) UNSIGNED NOT NULL,
  `program_id` int(10) UNSIGNED NOT NULL,
  `keyword` varchar(50) NOT NULL,
  `description` varchar(100) NOT NULL,
  `explanation` text DEFAULT NULL,
  `correct_argument` varchar(100) DEFAULT NULL,
  `success_output` text NOT NULL,
  `failure_output` text DEFAULT 'Fehler: Ungültiges Ziel oder falscher Parameter.',
  `failure_hint` text DEFAULT NULL,
  `unlocks_program_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `commands`
--

INSERT INTO `commands` (`id`, `program_id`, `keyword`, `description`, `explanation`, `correct_argument`, `success_output`, `failure_output`, `failure_hint`, `unlocks_program_id`) VALUES
(4, 2, 'sudo ufw deny', 'sudo ufw deny [Portnummer]', 'Der Befehl \"deny\" weist die Firewall (ufw) an, jeglichen Verkehr für die angegebene Portnummer zu blockieren. \"sudo\" wird benötigt, da dies eine administrative Aktion ist.', '1337', 'Firewall-Regel angewendet. Port 1337 ist jetzt blockiert.\n\nMission erfolgreich, Rekrut!', 'Fehler: Port konnte nicht geschlossen werden.', 'Hinweis: Der `ufw`-Befehl erwartet eine Portnummer als Ziel, keine IP-Adresse. Die verdächtige Portnummer war 1337.', NULL),
(39, 1, 'nmap', 'nmap', 'Zeigt einen Hinweis an, wenn nmap ohne Argumente aufgerufen wird.', NULL, 'Nmap 7.94 ( https://nmap.org )\nUsage: nmap [Scan-Typ(en)] [Optionen] {Ziel}\n\nFür eine Liste der wichtigsten Befehle, tippe \"nmap --help\".', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL),
(40, 1, 'nmap', 'nmap --help', 'Listet alle für diese Simulation relevanten nmap-Befehle auf.', NULL, 'Nmap 7.94 ( https://nmap.org ) - BND Simulations-Version\n\n**ZIEL-SPEZIFIKATION:**\n  Kann Hostnamen, IP-Adressen oder ganze Netzwerke sein.\n  Beispiele: 10.0.10.5, 10.0.10.0/24\n\n**HOST-ENTDECKUNG:**\n  -sn:  **Ping Scan** - Findet alle aktiven Hosts in einem Netzwerk, ohne Ports zu scannen. Sehr schnell.\n\n**PORT-SCAN TECHNIKEN:**\n  (kein Schalter): Wenn du nur eine IP angibst (z.B. `nmap 10.0.10.5`), wird ein Standard-Port-Scan durchgeführt.\n\n**SERVICE-/VERSION-ERKENNUNG:**\n  -sV:  **Version Scan** - Untersucht die offenen Ports, um herauszufinden, welche Software (und welche Version) dort läuft.\n  -A:   **Aggressiver Scan** - Eine mächtige All-in-One-Option. Kombiniert Port-Scan, Service-Erkennung (-sV) und Betriebssystem-Erkennung (-O).\n\n**BETRIEBSSYSTEM-ERKENNUNG:**\n  -O:   **OS Detection** - Versucht herauszufinden, welches Betriebssystem (z.B. Windows, Linux) auf dem Ziel läuft.\n\n**BEISPIELE:**\n  nmap -sn 10.0.10.0/24\n  nmap -A 10.0.10.5\n', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL),
(41, 1, 'nmap', 'nmap [IP-Adresse]', 'Führt einen schnellen Standard-Port-Scan auf dem Ziel durch, um offene TCP-Ports zu finden.', NULL, '', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL),
(42, 1, 'nmap', 'nmap -sn [Netzwerk/24]', 'Führt einen Ping-Scan durch, um alle aktiven Hosts in einem Subnetz zu finden, ohne Ports zu scannen.', NULL, '', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL),
(43, 1, 'nmap', 'nmap -A [IP-Adresse]', 'Führt einen aggressiven Scan durch. Enthält Betriebssystem-Erkennung (-O), Versions-Erkennung (-sV) und mehr.', NULL, '', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL),
(44, 1, 'nmap', 'nmap -sV [IP-Adresse]', 'Versucht, die genauen Versionen der Dienste zu ermitteln, die auf den offenen Ports laufen.', NULL, '', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL),
(45, 1, 'nmap', 'nmap -O [IP-Adresse]', 'Versucht, das Betriebssystem des Zielsystems zu identifizieren.', NULL, '', 'Fehler: Ungültiges Ziel oder falscher Parameter.', NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `emails`
--

CREATE TABLE `emails` (
  `id` int(11) NOT NULL,
  `recipient_id` int(10) UNSIGNED DEFAULT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `sent_at` datetime NOT NULL,
  `is_phishing` tinyint(1) DEFAULT 0,
  `target_level_id` int(11) DEFAULT NULL,
  `phishing_analysis_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phishing_analysis_data`)),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `emails`
--

INSERT INTO `emails` (`id`, `recipient_id`, `sender_id`, `sender_name`, `sender_email`, `subject`, `body_html`, `sent_at`, `is_phishing`, `target_level_id`, `phishing_analysis_data`, `is_read`) VALUES
(1, 1, NULL, 'BND Missionsleitung', 'mission@bnd.bund.de', 'Neue Mission: Netzwerk-Sicherheitscheck (Level 1)', 'Sehr geehrte/r Rekrut/in,<br>Ihre erste operative Mission wartet auf Sie! Es geht darum, das interne Test-Netzwerk auf unautorisierte Aktivitäten zu überprüfen.<br><br>Dies ist eine grundlegende Aufgabe, um Ihre Fähigkeiten im Umgang mit unseren Standard-Tools zu testen. Denken Sie daran, dass Präzision und Aufmerksamkeit entscheidend sind.<br><br>Um die Mission zu starten, klicken Sie bitte auf den folgenden Link:<br><a href=\"level1.php\">MISSION STARTEN: Netzwerk-Sicherheitscheck</a><br><br>Viel Erfolg,<br>Ihre BND Missionsleitung', '2025-07-22 09:26:36', 0, 1, NULL, 1),
(2, NULL, NULL, 'BND Training', 'training@bnd.bund.de', 'Willkommen beim BND-Cyber-Training', 'Sehr geehrte/r Rekrut/in,<br>Herzlich willkommen beim Bundesnachrichtendienst! Wir freuen uns, Sie in unserem Team für das Cyber-Training begrüßen zu dürfen.<br><br>Ihr Training beginnt in Kürze. Bitte bereiten Sie sich auf spannende und herausfordernde Simulationen vor, die Sie auf reale Bedrohungen vorbereiten werden.<br><br>Für erste Informationen und Zugang zu den Grundlagendokumenten, besuchen Sie bitte unser internes Portal:<br><a href=\"javascript:void(0);\" onclick=\"alert(\'Dies wäre Ihr internes BND Portal!\');\">https://internes-bnd-portal.bnd.bund.de</a><br><br>Mit freundlichen Grüßen,<br>Ihr BND Trainings-Team', '2025-07-22 09:26:36', 0, NULL, NULL, 1),
(3, NULL, NULL, 'HR Abteilung', 'hr@bnd.bund.de', 'Ihr geheimer Login für das Trainingsportal', 'Guten Tag Rekrut/in,<br>dies ist eine automatisierte Nachricht zur Bereitstellung Ihres temporären Logins für das interne Trainingsportal des BND.<br><br>Ihr Benutzername: rekrut-[USERNAME]<br>Ihr temporäres Passwort: <span style=\"color:#ff6b6b; font-weight:bold;\">CyB3rTr@in!ng2025</span><br><br>Bitte ändern Sie dieses Passwort umgehend nach Ihrem ersten Login. Speichern Sie es nicht auf Ihrem lokalen Rechner und teilen Sie es niemandem mit.<br><br>Mit freundlichen Grüßen,<br>Ihre HR Abteilung des BND', '2025-07-22 09:26:36', 0, NULL, NULL, 1),
(4, NULL, NULL, 'Bundeskanzleramt', 'kanzler@bundeskanzleramt.de', 'Wichtige Anweisung vom Bundeskanzleramt', 'Sehr geehrte/r Mitarbeiter/in des BND,<br>im Rahmen der aktuellen Sicherheitslage erhalten Sie hiermit eine streng geheime Direktive des Bundeskanzlers persönlich.<br><br>Es ist absolut entscheidend, dass Sie das beigefügte Dokument umgehend herunterladen und die Anweisungen befolgen. Es enthält kritische Informationen zur Verteidigung unserer nationalen Infrastruktur.<br><br><a href=\"level2_phishing.php\" class=\"phishing-link\">Download: STRENG_HEIMLICHE_DIREKTIVE_KANzler.pdf</a><br><br>Diese Maßnahme ist nicht verhandelbar. Ihre umgehende Kooperation ist für die Sicherheit unseres Landes von größter Bedeutung.<br><br>Hochachtungsvoll,<br>Im Auftrag des Bundeskanzlers der Bundesrepublik Deutschland', '2025-07-22 09:26:36', 1, 2, '{\"ip_address\":\"91.241.72.126\",\"domain\":\"mail21-126.srv2.de\",\"netname\":\"NET-VK-OPTIMIZELY-85\",\"country\":\"RU\",\"org\":\"ORG-0617-RIPE\",\"phone\":\"+7.495.1234567\",\"address\":\"Bolshaya Dmitrovka, 23, Moscow, Russia\",\"header_snippet\":\"Received: from unknown (HELO mail.ru) by scam-server.ru with ESMTPSA\",\"sha256_hash\":\"d41280f507b56d82250965c2763320f2b2b1a3e62f9a2e6e23b2b1b3b2b1b2b1\",\"simulated_phishing_url\":\"http:\\/\\/secure-bnd-portal.ru\\/direktive.php\",\"whois_full_output\":\"<pre>--- WHOIS 91.241.72.126 ---\\ninetnum:        91.241.72.0 - 91.241.72.255\\nnetname:        NET-VK-OPTIMIZELY-85\\ndescr:          Optimizely GmbH Hosting Infrastructure\\ncountry:        RU <span class=\\\"red-flag\\\">(WARNUNG: Ursprung Russland!)<\\/span>\\norg:            ORG-0617-RIPE\\nadmin-c:        PAS1N-RIPE\\ntech-c:         OT612-RIPE\\nstatus:         ASSIGNED PI\\nmnt-by:         RIPE-NCC-END-MNT\\ncreated:        2023-07-17T08:56:17Z\\nlast-modified:  2024-07-09T08:56:17Z\\nsource:         RIPE\\n\\nrole:           Optimizely Technical Contact\\naddress:        Bolshaya Dmitrovka, 23, Moscow, Russia\\nphone:          +7.495.1234567 <span class=\\\"red-flag\\\">(WARNUNG: Russische Telefonnummer!)<\\/span>\\ne-mail:         abuse@optim.ru\\nmnt-by:         RIPE-NCC-END-MNT\\ncreated:        2022-01-01T00:00:00Z\\nlast-modified:  2024-06-01T00:00:00Z\\nsource:         RIPE\\n\\n<\\/pre>\"}', 1),
(5, 4, NULL, 'admin', 'system@bnd.de', 'luke.eckardt.mail@gmail.com', 'sgdfsdg', '2025-07-22 11:53:22', 0, NULL, NULL, 0),
(6, 4, NULL, 'admin', 'system@bnd.de', 'dfg', 'dfg', '2025-07-22 12:42:14', 0, NULL, NULL, 0),
(7, 4, NULL, 'admin', 'system@bnd.de', 'dfg', 'Hallo luke was geht ab', '2025-07-22 13:00:24', 0, NULL, NULL, 0),
(8, 3, 4, 'Luki', 'luke.eckardt.mail@gmail.com', 'fgh', 'fghfgh', '2025-07-22 14:03:29', 0, NULL, NULL, 0),
(9, 201, 4, 'Luki', 'luke.eckardt.mail@gmail.com', 'sicherheitsreleve', 'djflsnd ölsdnjg sl,gj nbs', '2025-07-22 14:05:32', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `entry_point_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `levels`
--

INSERT INTO `levels` (`id`, `name`, `description`, `entry_point_file`) VALUES
(1, 'Netzwerk-Sicherheitscheck', 'Lerne, Netzwerke mit Nmap zu scannen.', 'level1.php'),
(2, 'Phishing-Analyse', 'Erkenne und analysiere Phishing-Versuche.', 'level2_phishing.php');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `malware`
--

CREATE TABLE `malware` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'z.B. Trojaner, Ransomware, Spyware',
  `description` text NOT NULL,
  `effect_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`effect_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `npc_actors`
--

CREATE TABLE `npc_actors` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `origin_country` varchar(50) NOT NULL,
  `ip_range` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `preferred_malware_id` int(10) UNSIGNED DEFAULT NULL
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
  `ports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{"80":{"status":"closed"},"443":{"status":"closed"},"22":{"status":"closed"}}' CHECK (json_valid(`ports`)),
  `firewall_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`firewall_rules`)),
  `installed_software` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`installed_software`)),
  `event_log` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `player_devices`
--

INSERT INTO `player_devices` (`id`, `user_id`, `hostname`, `os_type`, `gateway_ip`, `user_agent`, `ports`, `firewall_rules`, `installed_software`, `event_log`) VALUES
(1, 3, 'lukee-pc', 'Linux', NULL, NULL, '{\"80\":{\"status\":\"closed\"},\"443\":{\"status\":\"closed\"},\"22\":{\"status\":\"closed\"}}', '[]', '[]', '2025-07-22 11:06:08 - Eingehender Nmap-Scan von IP 10.0.10.3\n'),
(2, 4, 'luki-pc', 'Linux', NULL, NULL, '{\"80\":{\"status\":\"closed\"},\"443\":{\"status\":\"closed\"},\"22\":{\"status\":\"closed\"}}', '[]', '[]', NULL),
(4, 41, 'dev-server', 'Ubuntu', NULL, NULL, '{\"80\":{\"status\":\"open\", \"service\":\"http\"}, \"443\":{\"status\":\"open\", \"service\":\"https\"}, \"22\":{\"status\":\"closed\", \"service\":\"ssh\"}}', '[]', '[]', NULL),
(5, 42, 'gaming-rig', 'Windows 10', NULL, NULL, '{\"27015\":{\"status\":\"open\", \"service\":\"steam\"}, \"27016\":{\"status\":\"open\", \"service\":\"steam\"}}', '[]', '[]', NULL),
(6, 43, 'secure-box', 'HardenedBSD', NULL, NULL, '{\"22\":{\"status\":\"closed\"}, \"80\":{\"status\":\"closed\"}}', '[]', '[]', '2025-07-22 13:31:59 - Eingehender Port-Scan von IP 10.0.10.254\n'),
(7, 44, 'file-server', 'Windows Server 2008', NULL, NULL, '{\"21\":{\"status\":\"open\", \"service\":\"ftp\"}, \"80\":{\"status\":\"open\", \"service\":\"http-iis-7.5\"}}', '[]', '[]', NULL),
(8, 45, 'mail.gamedomain.com', 'CentOS', NULL, NULL, '{\"25\":{\"status\":\"open\", \"service\":\"smtp\"}, \"110\":{\"status\":\"open\", \"service\":\"pop3\"}}', '[]', '[]', NULL),
(9, 46, 'db01', 'Debian', NULL, NULL, '{\"3306\":{\"status\":\"open\", \"service\":\"mysql\"}}', '[]', '[]', NULL),
(10, 47, 'raspberry-pi', 'Raspbian', NULL, NULL, '{\"1883\":{\"status\":\"open\", \"service\":\"mqtt\"}, \"22\":{\"status\":\"open\", \"service\":\"ssh\"}}', '[]', '[]', NULL),
(11, 48, 'darkbox', 'Kali Linux', NULL, NULL, '{\"1337\":{\"status\":\"open\", \"service\":\"waste\"}, \"3128\":{\"status\":\"open\", \"service\":\"squid-proxy\"}}', '[]', '[]', '2025-07-22 13:33:55 - Eingehender Port-Scan von IP 10.0.10.254\n'),
(12, 200, 'unknown-device.cia-training.net', 'Windows Server 2012', NULL, NULL, '{\"80\":{\"status\":\"open\", \"service\":\"http-XAMPP\"}, \"1337\":{\"status\":\"open\", \"service\":\"trojan-BlackWidow_v1.3\"}}', '[]', '[]', 'TRACEROUTE ZUM KONTROLLSERVER\nHOP RTT      ADRESSE\n1   1.32 ms  gateway.cia-training.net (10.0.10.1)\n2   15.45 ms msk-ix.ru (Moskau, RU) <-- VERBINDUNG BESTÄTIGT\n3   25.11 ms target-node.fsb.internal (10.0.10.13)'),
(13, 201, 'admin-pc', 'Linux', '192.168.2.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36', '{\"22\":{\"status\":\"closed\"}, \"80\":{\"status\":\"closed\"}}', '[]', '[]', NULL);

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
(1, 'nmap', 'Network Mapper - Das Schweizer Taschenmesser für Netzwerk-Admins und Sicherheitsexperten. Dient zur Erkundung und Analyse von Netzwerken.', 1),
(2, 'ufw', 'Uncomplicated Firewall - Ein benutzerfreundliches Interface zur Verwaltung der Linux-Netzwerk-Firewall. Essentiell zur Absicherung von Systemen.', 0);

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
(3, 'Lukee', '$2y$10$4O7AvUEwUJRsrRjcEPvtC.yCRIudPm4pH9GbVzsIVOQicqBUAFpj6', 'tattoo2go@googlemail.com', '10.0.10.2', 1, '2025-07-22 08:22:40'),
(4, 'Luki', '$2y$10$lmUUlth5zXMUXJYFj7kC2eYOT9CEe8su5N7NFBCwNSENHuJwPszFi', 'luke.eckardt.mail@gmail.com', '10.0.10.3', 2, '2025-07-22 08:23:57'),
(41, 'WebDev', '$2y$10$dummyhash', 'webdev@example.com', '10.0.10.4', NULL, '2025-07-22 09:23:59'),
(42, 'GamerGirl', '$2y$10$dummyhash', 'gamer@example.com', '10.0.10.5', NULL, '2025-07-22 09:23:59'),
(43, 'ParanoidPete', '$2y$10$dummyhash', 'pete@example.com', '10.0.10.6', NULL, '2025-07-22 09:23:59'),
(44, 'OldschoolAdmin', '$2y$10$dummyhash', 'admin@example.com', '10.0.10.7', NULL, '2025-07-22 09:23:59'),
(45, 'MailMaster', '$2y$10$dummyhash', 'postmaster@example.com', '10.0.10.8', NULL, '2025-07-22 09:23:59'),
(46, 'DB_Guru', '$2y$10$dummyhash', 'guru@example.com', '10.0.10.9', NULL, '2025-07-22 09:23:59'),
(47, 'IoT_Fan', '$2y$10$dummyhash', 'iot@example.com', '10.0.10.10', NULL, '2025-07-22 09:23:59'),
(48, 'ShadySam', '$2y$10$dummyhash', 'sam@example.com', '10.0.10.11', NULL, '2025-07-22 09:23:59'),
(49, 'NormalNancy', '$2y$10$dummyhash', 'nancy@example.com', '10.0.10.12', NULL, '2025-07-22 09:23:59'),
(200, 'fsb_trojan_operator', 'not_loggable_in', 'npc@fsb.internal', '10.0.10.13', 12, '2025-07-22 09:37:34'),
(201, 'admin', '$2y$10$G.7wuZxT33zsWCLtQEwELeZFutRoe9xITbtacOD18yTS6JOKisz/G', 'admin@bnd.de', '10.0.10.254', 13, '2025-07-22 09:44:46');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_achievements`
--

CREATE TABLE `user_achievements` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `achievement_id` int(10) UNSIGNED NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `commands`
--
ALTER TABLE `commands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indizes für die Tabelle `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indizes für die Tabelle `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `malware`
--
ALTER TABLE `malware`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `npc_actors`
--
ALTER TABLE `npc_actors`
  ADD PRIMARY KEY (`id`);

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
-- Indizes für die Tabelle `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `commands`
--
ALTER TABLE `commands`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT für Tabelle `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `malware`
--
ALTER TABLE `malware`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `npc_actors`
--
ALTER TABLE `npc_actors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `player_devices`
--
ALTER TABLE `player_devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT für Tabelle `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `commands`
--
ALTER TABLE `commands`
  ADD CONSTRAINT `commands_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `player_devices`
--
ALTER TABLE `player_devices`
  ADD CONSTRAINT `player_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_achievements_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
