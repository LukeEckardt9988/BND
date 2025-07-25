<?php
// BND/game_logic/level1.php

function get_level_step_definition($step_number_param)
{
    $level_steps = [
        // DEINE IDEE: Der Startbefehl ist jetzt der erste offizielle Schritt des Levels.
        0 => [
            'expected_command' => 'start training',
            'console_output' => "Ausbildungsprogramm gestartet. Anweisungen finden Sie in Ihrem Postfach.",
             'notification_title' => 'Erste Aufgabe',
            'notification_message' => "Scannen Sie das Netzwerk mit dem Befehl: <code>nmap -sn 10.0.10.0/24</code>",
            'email_id_to_send' => 6, // E-Mail mit Link zu nmap.html        
            'next_step_id' => 1 // Leitet zu Schritt 1 (dem eigentlichen Scan) über
        ],
        1 => [
            'expected_command' => 'nmap -sn 10.0.10.0/24',
            'console_output' => "Starting Nmap Host Discovery Scan...\nNmap scan report for 10.0.10.1 (Gateway)\nHost is up.\nNmap scan report for 10.0.10.13 (unauthorized_device)\nHost is up.",
            'notification_title' => 'Unautorisiertes Gerät!',
            'notification_message' => "Der Scan hat ein nicht autorisiertes Gerät unter der IP <b>10.0.10.13</b> aufgedeckt. Führen Sie einen aggressiven Scan darauf aus: <code>nmap -A 10.0.10.13</code>",
            'next_step_id' => 2
        ],
        2 => [
            'expected_command' => 'nmap -A 10.0.10.13',
            'console_output' => "Starting Nmap Aggressive Scan for 10.0.10.13...\nHost is up.\n\nPORT     STATE SERVICE VERSION\n1337/tcp open  trojan  BlackWidow v1.3",
            'notification_title' => 'Trojaner entdeckt!',
            'notification_message' => "Der Scan bestätigt einen Trojaner auf Port <b>1337</b>. Blockieren Sie den Port mit: <code>sudo ufw deny 1337</code>",
            'next_step_id' => 3
        ],
        3 => [
            'expected_command' => 'sudo ufw deny 1337',
            'console_output' => "Firewall-Regel angewendet. Port 1337 ist jetzt blockiert.\n\n<b>Mission erfolgreich abgeschlossen.</b>\n\n- Missionsbericht wurde an Ihr Postfach gesendet.\n- Geben Sie '<b>clear</b>' ein, um das Terminal für die nächste Mission vorzubereiten.",
            'email_id_to_send' => 5, // "Mission bestanden" E-Mail
            'next_step_id' => 4
        ],
        4 => [
            'expected_command' => 'clear',
            'console_output' => "Terminal wird für die nächste Operation vorbereitet...",
            'email_id_to_send' => 3, // Phishing-Mail
            'mission_completed' => true,
            'next_step_id' => null
        ]
    ];
    return $level_steps[$step_number_param] ?? null;
}

// Die handle_level_command Funktion bleibt unverändert
if (!function_exists('handle_level_command')) {
    function handle_level_command($user_id, $user_command_raw, $mission_id, $current_step_number, $pdo)
    {
        $response = [
            'success' => false,
            'console_output' => "Befehl '" . htmlspecialchars($user_command_raw) . "' ist für diesen Missionsschritt nicht korrekt.",
        ];
        $step_definition = get_level_step_definition($current_step_number);
        if ($step_definition && strtolower($user_command_raw) === strtolower($step_definition['expected_command'])) {
            $response = array_merge($response, $step_definition);
            $response['success'] = true;
        }
        return $response;
    }
}
