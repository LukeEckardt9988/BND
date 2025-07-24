<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    echo json_encode(['output' => 'Fehler: Nicht autorisierter Zugriff.']);
    exit;
}

require 'db_connect.php'; //

// Globale Variablen & Befehls-Parsing
$user_id = $_SESSION['user_id'];
$stmt_user = $pdo->prepare("SELECT ip_address FROM users WHERE id = :id");
$stmt_user->execute([':id' => $user_id]);
$user_ip = $stmt_user->fetchColumn();

$full_command = trim($_POST['command']);
$parts = explode(' ', $full_command);
$program_name = strtolower($parts[0]);
$arguments = array_slice($parts, 1);

// ==================================================================
// BEFEHLS-ROUTER
// ==================================================================

$response = ['output' => "Befehl nicht gefunden: '" . htmlspecialchars($program_name) . "'."];

switch ($program_name) {
    case 'nmap':
        // Fall 1: "nmap" ohne Argumente
        if (empty($arguments)) {
            $stmt = $pdo->prepare("SELECT success_output FROM commands WHERE description = 'nmap'");
            $stmt->execute();
            $response['output'] = $stmt->fetchColumn();
            break;
        }

        // Fall 2: "nmap --help"
        if ($arguments[0] === '--help') {
            $stmt = $pdo->prepare("SELECT success_output FROM commands WHERE description = 'nmap --help'");
            $stmt->execute();
            $response['output'] = $stmt->fetchColumn();
            break;
        }

        // Fall 3: Dynamischer Scan (Subnetz oder Einzel-IP)
        $target = end($arguments); // Das Ziel ist immer das letzte Argument
        $flags = array_slice($arguments, 0, -1); // Alle Argumente davor sind Schalter

        // Subnetz-Scan
        if (strpos($target, '/') !== false) {
            $subnet_prefix = substr($target, 0, strrpos($target, '.')) . '.';
            $stmt = $pdo->prepare("SELECT ip_address FROM users WHERE ip_address LIKE :subnet AND ip_address != :scanner_ip");
            $stmt->execute([':subnet' => $subnet_prefix . '%', ':scanner_ip' => $user_ip]);
            $hosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $output = "Starting Nmap Host Discovery Scan...\n";
            if (empty($hosts)) $output .= "Nmap done: 0 hosts up in scanned range.";
            else {
                foreach ($hosts as $host) $output .= "Nmap scan report for " . $host['ip_address'] . "\nHost is up.\n";
            }
            $response['output'] = $output;
            break;
        }

        // Einzel-IP-Scan
        if (filter_var($target, FILTER_VALIDATE_IP)) {
            $stmt = $pdo->prepare("SELECT u.username, d.* FROM player_devices d JOIN users u ON d.user_id = u.id WHERE u.ip_address = :ip");
            $stmt->execute([':ip' => $target]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$device) {
                $response['output'] = "Host " . htmlspecialchars($target) . " scheint down zu sein.";
                break;
            }

            $output = "Starte Nmap Scan für " . htmlspecialchars($target) . "...\n";
            if (in_array('-A', $flags) || in_array('-O', $flags)) {
                $output .= "  Betriebssystem: " . htmlspecialchars($device['os_type']) . "\n";
            }

            $ports_data = json_decode($device['ports'], true);
            $found_open_port = false;
            $output .= "PORT    STATE    SERVICE\n";

            if (!empty($ports_data)) {
                foreach ($ports_data as $port => $details) {
                    if ($details['status'] === 'open') {
                        $found_open_port = true;
                        $service = htmlspecialchars($details['service'] ?? 'unknown');
                        // Logik für -sV (Version Scan)
                        if (in_array('-sV', $flags) || in_array('-A', $flags)) {
                            // Hier könnte man eine komplexere Version simulieren, für den Anfang nehmen wir den Service-Namen
                            $service .= " (Version " . rand(1, 5) . "." . rand(0, 9) . ")";
                        }
                        $output .= str_pad($port . "/tcp", 8) . str_pad("open", 9) . "$service\n";
                    }
                }
            }

            if (!$found_open_port) {
                $output .= "Alle 1000 gescannten Ports sind geschlossen.";
            }
            $response['output'] = $output;
        } else {
            $response['output'] = "Nmap Fehler: Ungültiges Ziel '" . htmlspecialchars($target) . "'.";
        }
        break;

    case 'analyze_email_header':
        if (empty($arguments) || !is_numeric($arguments[0])) {
            $response['output'] = "Verwendung: analyze_email_header [E-Mail-ID]";
            break;
        }
        $email_id = (int)$arguments[0];

        // Hole die E-Mail-Daten, insbesondere die Phishing-Analyse-Daten
        $stmt_email = $pdo->prepare("SELECT body_html, phishing_analysis_data FROM emails WHERE id = :email_id AND recipient_id = :user_id");
        $stmt_email->execute([':email_id' => $email_id, ':user_id' => $user_id]);
        $email_data = $stmt_email->fetch(PDO::FETCH_ASSOC);

        if ($email_data) {
            $analysis_data = json_decode($email_data['phishing_analysis_data'], true);
            if ($analysis_data && isset($analysis_data['header_snippet'])) {
                // Den tatsächlichen Header-Snippet nutzen
                $response['output'] = "Simulierter E-Mail-Header für E-Mail ID " . $email_id . ":\n\n" . $analysis_data['header_snippet'] . "\n\n[Inhalt der E-Mail würde hier folgen]";
            } else {
                // Wenn keine speziellen Phishing-Daten vorhanden sind, zeige eine Standard-Info
                $response['output'] = "Simulierter E-Mail-Header für E-Mail ID " . $email_id . ":\n\nKeine spezifischen Header-Daten für Analyse verfügbar.\n\n" . strip_tags(substr($email_data['body_html'], 0, 200)) . "...";
            }
        } else {
            $response['output'] = "Fehler: E-Mail mit dieser ID nicht gefunden oder Sie haben keine Berechtigung.";
        }
        break;

    case 'whois':
        if (empty($arguments) || !filter_var($arguments[0], FILTER_VALIDATE_IP)) {
            $response['output'] = "Verwendung: whois [IP-Adresse]";
            break;
        }
        $ip_address = $arguments[0];

        // Suche nach einer E-Mail, die diese IP in ihren phishing_analysis_data hat
        // Dies ist ein einfacher Weg, um "echte" Whois-Daten zu simulieren
        $stmt_whois = $pdo->prepare("SELECT phishing_analysis_data FROM emails WHERE JSON_EXTRACT(phishing_analysis_data, '$.ip_address') = :ip_address LIMIT 1");
        $stmt_whois->execute([':ip_address' => $ip_address]);
        $phishing_data_row = $stmt_whois->fetch(PDO::FETCH_ASSOC);

        if ($phishing_data_row) {
            $phishing_data = json_decode($phishing_data_row['phishing_analysis_data'], true);
            if (isset($phishing_data['whois_full_output'])) {
                $response['output'] = $phishing_data['whois_full_output'];
            } else {
                $response['output'] = "WHOIS-Daten für " . htmlspecialchars($ip_address) . " gefunden, aber kein vollständiger Output hinterlegt.";
            }
        } else {
            // Generische oder leere Antwort für unbekannte IPs
            $response['output'] = "WHOIS-Abfrage für " . htmlspecialchars($ip_address) . " abgeschlossen.\nKeine relevanten Informationen gefunden.";
        }
        break;

    case 'help':
        $response['output'] = "Verfügbare Befehle:\n";
        $response['output'] .= "  nmap [Optionen] [Ziel] - Netzwerk-Scanner (tippe 'nmap --help' für Details)\n";
        $response['output'] .= "  analyze_email_header [E-Mail-ID] - Analysiert den Header einer E-Mail.\n";
        $response['output'] .= "  whois [IP-Adresse] - Führt eine WHOIS-Abfrage durch.\n";
        $response['output'] .= "  clear - Löscht die Konsole.\n";
        break;

    default:
        // Der Standardfall bleibt gleich
        // Versuche, den Befehl in der 'commands'-Tabelle zu finden
        $stmt = $pdo->prepare("SELECT success_output, failure_output, failure_hint FROM commands WHERE keyword = :keyword AND correct_argument IS NULL LIMIT 1");
        $stmt->execute([':keyword' => $program_name]);
        $cmd_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cmd_info) {
            $response['output'] = $cmd_info['success_output'];
        } else {
            $response['output'] = "Befehl nicht gefunden: '" . htmlspecialchars($program_name) . "'.";
        }
        break;
}

echo json_encode($response);
