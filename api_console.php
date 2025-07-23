<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    echo json_encode(['output' => 'Fehler: Nicht autorisierter Zugriff.']);
    exit;
}

require 'db_connect.php';

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
            if(empty($hosts)) $output .= "Nmap done: 0 hosts up in scanned range.";
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
                             $service .= " (Version " . rand(1,5) . "." . rand(0,9) . ")";
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

    // Hier können weitere Befehle wie 'whois', 'ufw' etc. folgen
    default:
        // Optional: Hier könnte man noch die statische `commands`-Tabelle für andere Programme durchsuchen
        break;
}

echo json_encode($response);