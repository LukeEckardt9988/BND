<?php
// BND/game_logic/level1.php

// =======================================================================
// === FEHLERHAFTER INITIALISIERUNGS-BLOCK WURDE ENTFERNT ===
// =======================================================================
// Der alte Code, der versucht hat, eine 'name'-Spalte zu befüllen,
// wurde entfernt, da er nicht mehr zur Datenbankstruktur passt und
// für die aktuelle Spiellogik nicht benötigt wird.
// Die Missionsschritte werden jetzt dynamisch von der mission_console.php
// und der handle_level_command Funktion verwaltet.


// Diese Funktion definiert die einzelnen Schritte und deren erwartete Befehle
function get_level_step_definition($step_number_param)
{
    $level_steps = [
        1 => [ // Step 1: Network Scan (nmap -sn)
            'expected_command' => 'nmap -sn 10.0.10.0/24',
            'console_output' => "Starting Nmap Host Discovery Scan...\nNmap scan report for 10.0.10.1 (Gateway)\nHost is up.\nNmap scan report for 10.0.10.3 (luki-pc)\nHost is up.\nNmap scan report for 10.0.10.4 (dev-server)\nHost is up.\nNmap scan report for 10.0.10.13 (unauthorized_device)\nHost is up.\nNmap scan report for 10.0.10.254 (admin-pc)\nHost is up.\nNmap done: 5 hosts up in scanned range.",
            'notification_title' => 'Scan abgeschlossen!',
            'notification_message' => 'Das Netzwerk wurde erfolgreich gescannt. Analysieren Sie die Ergebnisse sorgfältig. Eine neue Anweisung wurde Ihnen per E-Mail zugesendet.',
            'email_id_to_send' => 2, // E-Mail für nmap -A Befehl
            'next_step_id' => 2,
            'description_for_display' => 'Führen Sie einen Host-Discovery-Scan durch, um aktive Geräte im Subnetz 10.0.10.0/24 zu finden.'
        ],
        2 => [ // Step 2: Aggressive Scan (nmap -A)
            'expected_command' => 'nmap -A 10.0.10.13',
            'console_output' => "Starting Nmap Aggressive Scan for 10.0.10.13...\nNmap scan report for unknown-device.cia-training.net (10.0.10.13)\nHost is up (0.005s latency).\n\nPORT     STATE SERVICE VERSION\n80/tcp   open  http    Apache httpd 2.4.X ((Win) XAMPP)\n1337/tcp open  trojan  BlackWidow v1.3\n\nOS details: Windows Server 2012",
            'notification_title' => 'Detailanalyse abgeschlossen!',
            'notification_message' => 'Der aggressive Scan hat wichtige Informationen geliefert. Sie haben nun Zugriff auf das "ufw"-Tool. Eine neue Anweisung wurde Ihnen per E-Mail zugesendet.',
            'email_id_to_send' => 4, // E-Mail für sudo ufw deny Befehl
            'next_step_id' => 3,
            'description_for_display' => 'Führen Sie einen aggressiven Scan auf dem verdächtigen Host (10.0.10.13) durch, um offene Ports und Dienste zu finden.'
        ],
        3 => [ // Step 3: Block Port 1337 (sudo ufw deny)
            'expected_command' => 'sudo ufw deny 1337',
            'console_output' => "Firewall-Regel angewendet. Port 1337 ist jetzt blockiert.",
            'notification_title' => 'Port blockiert!',
            'notification_message' => 'Der kritische Port 1337 wurde erfolgreich blockiert. Level 1 ist damit abgeschlossen. Eine neue Mission wartet in Ihrem Posteingang.',
            'email_id_to_send' => 3, // E-Mail für Phishing (startet Level 2)
            'mission_completed' => true,
            'next_step_id' => null,
            'description_for_display' => 'Geben Sie den Befehl ein, um den verdächtigen Port 1337 zu blockieren.'
        ]
    ];
    return $level_steps[$step_number_param] ?? null;
}

// Diese Funktion verarbeitet den Befehl des Spielers für das aktuelle Level
if (!function_exists('handle_level_command')) {
    function handle_level_command($user_id, $user_command_raw, $mission_id, $current_step_number, $pdo)
    {
        $response = [
            'success' => false,
            'console_output' => "Befehl '" . htmlspecialchars($user_command_raw) . "' ist für diesen Missionsschritt nicht korrekt. Versuchen Sie es erneut.",
            'notification_title' => null,
            'notification_message' => null,
            'email_id_to_send' => null,
            'mission_completed' => false,
            'next_step_id' => null
        ];

        $step_definition = get_level_step_definition($current_step_number);

        if ($step_definition && $user_command_raw === $step_definition['expected_command']) {
            $response['success'] = true;
            $response['console_output'] = $step_definition['console_output'];
            $response['notification_title'] = $step_definition['notification_title'];
            $response['notification_message'] = $step_definition['notification_message'];
            $response['email_id_to_send'] = $step_definition['email_id_to_send'];
            $response['mission_completed'] = $step_definition['mission_completed'] ?? false;
            $response['next_step_id'] = $step_definition['next_step_id'];
        }

        return $response;
    }
}
