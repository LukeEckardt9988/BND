<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    echo json_encode(['output' => 'Fehler: Nicht autorisierter Zugriff.']);
    exit;
}

require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$stmt_user = $pdo->prepare("SELECT ip_address FROM users WHERE id = :id");
$stmt_user->execute([':id' => $user_id]);
$user_ip = $stmt_user->fetchColumn();

$full_command = trim($_POST['command']);
$parts = explode(' ', $full_command);
$program_name = strtolower($parts[0]);
$arguments = array_slice($parts, 1);

$response = ['output' => "Befehl nicht gefunden: '" . htmlspecialchars($program_name) . "'."];

switch ($program_name) {
    case 'nmap':
        // Fall 1: "nmap" ohne Argumente (Output aus commands-Tabelle ID 39)
        if (empty($arguments)) {
            $stmt = $pdo->prepare("SELECT success_output FROM commands WHERE id = 39");
            $stmt->execute();
            $response['output'] = $stmt->fetchColumn();
            break;
        }

        // Fall 2: "nmap --help" (Output aus commands-Tabelle ID 40)
        if (isset($arguments[0]) && $arguments[0] === '--help') {
            $stmt = $pdo->prepare("SELECT success_output FROM commands WHERE id = 40");
            $stmt->execute();
            $response['output'] = $stmt->fetchColumn();
            break;
        }

        $target = end($arguments);
        $flags = array_slice($arguments, 0, -1);

        $command_id_for_output = null;

        // Versuche, den genauen Befehl in der commands-Tabelle zu finden und dessen Output zu nutzen
        if (in_array('-sn', $flags) && $target === '10.0.10.0/24') { // Angepasster Zielbereich
            $command_id_for_output = 42; // nmap -sn [Netzwerk/24]
        } elseif (in_array('-A', $flags) && $target === '10.0.10.13') {
            $command_id_for_output = 43; // nmap -A [IP-Adresse]
        } elseif (in_array('-sV', $flags) && $target === '10.0.10.13') {
            $command_id_for_output = 44; // nmap -sV [IP-Adresse]
        } elseif (in_array('-O', $flags) && filter_var($target, FILTER_VALIDATE_IP)) {
            $command_id_for_output = 45; // nmap -O [IP-Adresse]
        } elseif (filter_var($target, FILTER_VALIDATE_IP) && empty($flags)) {
            $command_id_for_output = 41; // nmap [IP-Adresse]
        }

        // Wenn ein spezifischer Command-ID gefunden wurde, hole den Output direkt aus der commands-Tabelle
        if ($command_id_for_output !== null) {
            $stmt_cmd_output = $pdo->prepare("SELECT success_output FROM commands WHERE id = :id");
            $stmt_cmd_output->execute([':id' => $command_id_for_output]);
            $output_from_db = $stmt_cmd_output->fetchColumn();
            if ($output_from_db) {
                $response['output'] = $output_from_db;
                break; // WICHTIG: Beendet den Switch, um Fallback-Logik zu überspringen
            }
        }

        // Fallback für nicht missionsspezifische Nmap-Befehle oder wenn kein passender Output in commands gefunden wurde
        if (filter_var($target, FILTER_VALIDATE_IP)) {
            $stmt = $pdo->prepare("SELECT d.* FROM player_devices d JOIN users u ON d.user_id = u.id WHERE u.ip_address = :ip");
            $stmt->execute([':ip' => $target]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$device) {
                $response['output'] = "Host " . htmlspecialchars($target) . " scheint down zu sein oder wurde im simulierten Netzwerk nicht gefunden.";
                break;
            }

            $output = "Starte Nmap Scan für " . htmlspecialchars($target) . "...\n";
            if (in_array('-A', $flags) || in_array('-O', $flags)) {
                $output .= "  Betriebssystem: " . htmlspecialchars($device['os_type']) . "\n";
            }

            $ports_data = json_decode($device['ports'], true);
            if (!empty($ports_data)) {
                $output .= "PORT    STATE    SERVICE\n";
                foreach ($ports_data as $port => $details) {
                    if ($details['status'] === 'open') {
                        $service = htmlspecialchars($details['service'] ?? 'unknown');
                        if (in_array('-sV', $flags) || in_array('-A', $flags)) {
                            $service .= " (Version " . rand(1, 5) . "." . rand(0, 9) . ")";
                        }
                        $output .= str_pad($port . "/tcp", 8) . str_pad("open", 9) . "$service\n";
                    }
                }
            } else {
                $output .= "Alle 1000 gescannten Ports sind geschlossen.";
            }
            $response['output'] = $output;
        } else {
            $response['output'] = "Nmap Fehler: Ungültiges Ziel '" . htmlspecialchars($target) . "'.";
        }
        break;

    case 'sudo': // Behandle sudo ufw deny
        if (isset($arguments[0]) && $arguments[0] === 'ufw' && isset($arguments[1]) && $arguments[1] === 'deny' && isset($arguments[2])) {
            $port_to_deny = $arguments[2];
            // Hole den Output direkt aus der commands-Tabelle für den ufw deny Befehl (ID 4)
            $stmt_ufw_output = $pdo->prepare("SELECT success_output FROM commands WHERE id = 4");
            $stmt_ufw_output->execute();
            $output_from_db = $stmt_ufw_output->fetchColumn();
            if ($output_from_db) {
                $response['output'] = $output_from_db;
            } else {
                $response['output'] = "Firewall-Regel angewendet. Port " . htmlspecialchars($port_to_deny) . " ist jetzt blockiert.";
            }
        } else {
            $response['output'] = "Syntax Fehler: sudo ufw deny [Portnummer].";
        }
        break;

    default:
        // Versuche, ob der Befehl als allgemeiner Befehl existiert (ohne program_id, nur keyword)
        $stmt = $pdo->prepare("SELECT success_output FROM commands WHERE keyword = :keyword LIMIT 1");
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
