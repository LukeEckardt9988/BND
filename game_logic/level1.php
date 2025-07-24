<?php
// BND/game_logic/level1.php

// =======================================================================
// === SICHERE INITIALISIERUNG DER MISSIONS-STRUKTUR                  ===
// =======================================================================
// Dieser Block löst den Foreign-Key-Fehler, indem er die Schritte
// in der korrekten Reihenfolge anlegt und verknüpft.

// 1. Definieren Sie die gesamte Kette Ihrer Mission-Schritte hier
$mission_chain = [
    // id => ['name' => '...', 'next_step_id' => id_des_nächsten_schritts],
    1 => ['name' => 'Netzwerk-Scan durchführen', 'next_step_id' => 2],
    2 => ['name' => 'Host-Analyse durchführen', 'next_step_id' => 3],
    3 => ['name' => 'Phishing-Mail analysieren', 'next_step_id' => 4],
    4 => ['name' => 'Trojaner-Port blockieren', 'next_step_id' => null] // Letzter Schritt, kein Nachfolger
];

try {
    // Eine Transaktion stellt sicher, dass alles oder nichts ausgeführt wird.
    $pdo->beginTransaction();

    // Schritt A: Alle Schritte VORAB OHNE Verknüpfung einfügen.
    // Das 'IGNORE' Schlüsselwort verhindert Fehler, falls die Schritte schon existieren
    // (z.B. wenn der User 'start training' mehrfach eingibt).
    $insert_statement = $pdo->prepare(
        "INSERT IGNORE INTO `mission_steps` (`id`, `name`, `next_step_id`) VALUES (:id, :name, NULL);"
    );

    foreach ($mission_chain as $id => $step) {
        $insert_statement->execute([
            ':id' => $id,
            ':name' => $step['name']
        ]);
    }

    // Schritt B: JETZT, wo alle IDs garantiert existieren, werden die Verknüpfungen gesetzt.
    $update_statement = $pdo->prepare(
        "UPDATE `mission_steps` SET `next_step_id` = :next_step_id WHERE `id` = :id;"
    );

    foreach ($mission_chain as $id => $step) {
        // Nur updaten, wenn es einen Nachfolger gibt.
        if ($step['next_step_id'] !== null) {
            $update_statement->execute([
                ':next_step_id' => $step['next_step_id'],
                ':id' => $id
            ]);
        }
    }

    // Wenn alles fehlerfrei durchlief, werden die Änderungen permanent gespeichert.
    $pdo->commit();
} catch (Exception $e) {
    // Wenn ein Fehler auftritt, werden alle Änderungen seit beginTransaction() verworfen.
    $pdo->rollBack();

    // Geben Sie eine klare Fehlermeldung zurück, damit Sie im Frontend sehen, was los ist.
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'DB-Initialisierungsfehler: Die Mission konnte nicht aufgesetzt werden. Details: ' . $e->getMessage()
    ]);
    exit(); // Wichtig: Brechen Sie die Ausführung hier ab.
}

// Diese Funktion wird von api_game_command.php aufgerufen, um die Definitionen abzurufen.
function get_level_step_definition($step_number_param)
{
    $level_steps = [
        1 => [ // Step 1: Network Scan (nmap -sn)
            'expected_command' => 'nmap -sn 10.0.10.0/24',
            'console_output' => "Starting Nmap Host Discovery Scan...\nNmap scan report for 10.0.10.1 (Gateway)\nHost is up.\nNmap scan report for 10.0.10.3 (luki-pc)\nHost is up.\nNmap scan report for 10.0.10.4 (dev-server)\nHost is up.\nNmap scan report for 10.0.10.13 (unauthorized_device)\nHost is up.\nNmap scan report for 10.0.10.254 (admin-pc)\nHost is up.\nNmap done: 5 hosts up in scanned range.",
            'notification_title' => 'Scan abgeschlossen!',
            'notification_message' => 'Das Netzwerk wurde erfolgreich gescannt. Analysieren Sie die Ergebnisse sorgfältig. Eine neue Anweisung wurde Ihnen per E-Mail zugesendet.',
            'email_id_to_send' => 2, // E-Mail für nmap -A Befehl (ID 2)
            'program_to_unlock_id' => null, // Kein Programm nach diesem Schritt freischalten
            'next_step_id' => 2, // Weiter zu Schritt 2 (nmap -A)
            'description_for_display' => 'Führen Sie einen Host-Discovery-Scan durch, um aktive Geräte im Subnetz 10.0.10.0/24 zu finden.'
        ],
        2 => [ // Step 2: Aggressive Scan (nmap -A)
            'expected_command' => 'nmap -A 10.0.10.13',
            'console_output' => "Starting Nmap Aggressive Scan for 10.0.10.13...\nNmap scan report for unknown-device.cia-training.net (10.0.10.13)\nHost is up (0.005s latency).\n\nPORT     STATE SERVICE VERSION\n80/tcp   open  http    Apache httpd 2.4.X ((Win) XAMPP)\n1337/tcp open  trojan  BlackWidow v1.3\n\nOS details: Windows Server 2012\n\nTRACEROUTE ZUM KONTROLLSERVER\nHOP RTT      ADRESSE\n1   1.32 ms  gateway.cia-training.net (10.0.10.1)\n2   15.45 ms msk-ix.ru (Moskau, RU)\n3   25.11 ms target-node.fsb.internal (10.0.10.13)\n\nNmap done: 1 IP address (1 host up) scanned in 1.25 seconds.",
            'notification_title' => 'Detailanalyse abgeschlossen!',
            'notification_message' => 'Der aggressive Scan hat wichtige Informationen geliefert. Sie haben nun Zugriff auf das "ufw"-Tool zur Firewall-Verwaltung. Eine neue Anweisung wurde Ihnen per E-Mail zugesendet.',
            'email_id_to_send' => 4, // E-Mail für sudo ufw deny Befehl (ID 4, neu erstellt)
            'program_to_unlock_id' => 2, // ufw freischalten
            'next_step_id' => 3, // Weiter zu Schritt 3 (sudo ufw deny)
            'description_for_display' => 'Führen Sie einen aggressiven Scan auf dem verdächtigen Host (10.0.10.13) durch, um offene Ports und Dienste zu finden.'
        ],
        3 => [ // Step 3: Block Port 1337 (sudo ufw deny)
            'expected_command' => 'sudo ufw deny 1337',
            'console_output' => "Firewall-Regel angewendet. Port 1337 ist jetzt blockiert.",
            'notification_title' => 'Port blockiert!',
            'notification_message' => 'Der kritische Port 1337 wurde erfolgreich blockiert und die Bedrohung eingedämmt. Level 1 ist damit abgeschlossen. Eine neue, kritische Mission wartet in Ihrem Posteingang. Überprüfen Sie Ihre E-Mails umgehend!',
            'email_id_to_send' => 3, // E-Mail für Phishing (ID 3)
            'program_to_unlock_id' => null,
            'mission_completed' => true,
            'next_step_id' => null,
            'description_for_display' => 'Geben Sie den Befehl ein, um den verdächtigen Port 1337 auf dem kompromittierten System zu blockieren.'
        ]
    ];
    return $level_steps[$step_number_param] ?? null;
}

if (!function_exists('handle_level_command')) {
    function handle_level_command($user_id, $user_command_raw, $mission_id, $current_step_number, $pdo)
    {
        $response = [
            'success' => false,
            'console_output' => 'Befehl nicht erkannt für diesen Missionsschritt.',
            'notification_title' => null,
            'notification_message' => null,
            'email_id_to_send' => null,
            'program_to_unlock_id' => null,
            'mission_completed' => false,
            'next_step_id' => null
        ];

        $step_definition = get_level_step_definition($current_step_number);

        if ($step_definition) {
            if ($user_command_raw === $step_definition['expected_command']) {
                $response['success'] = true;
                $response['console_output'] = $step_definition['console_output'];
                $response['notification_title'] = $step_definition['notification_title'];
                $response['notification_message'] = $step_definition['notification_message'];
                $response['email_id_to_send'] = $step_definition['email_id_to_send'];
                $response['program_to_unlock_id'] = $step_definition['program_to_unlock_id'];
                $response['mission_completed'] = $step_definition['mission_completed'] ?? false;
                $response['next_step_id'] = $step_definition['next_step_id'];

                // Aktualisiere die Beschreibung im mission_steps für den nächsten Schritt (oder Abschluss)
                if ($response['next_step_id'] !== null) {
                    $next_step_definition = get_level_step_definition($response['next_step_id']);
                    $next_step_display_desc = $next_step_definition['description_for_display'] ?? 'Neue Aufgabe erwartet.';
                    $stmt_update_desc = $pdo->prepare("INSERT INTO mission_steps (mission_id, step_number, description, next_step_id) VALUES (:mid, :snum, :desc, :next_snum) ON DUPLICATE KEY UPDATE description = VALUES(description), next_step_id = VALUES(next_step_id)");
                    $stmt_update_desc->execute([
                        ':mid' => $mission_id,
                        ':snum' => $response['next_step_id'],
                        ':desc' => $next_step_display_desc,
                        ':next_snum' => ($next_step_definition['next_step_id'] ?? NULL)
                    ]);
                } elseif ($response['mission_completed']) {
                    $stmt_update_desc = $pdo->prepare("UPDATE mission_steps SET description = :desc WHERE mission_id = :mid AND step_number = :snum");
                    $stmt_update_desc->execute([
                        ':desc' => $step_definition['description_for_display'],
                        ':mid' => $mission_id,
                        ':snum' => $current_step_number
                    ]);
                }

                // *********** Hier wird die Phishing-Analyse-Daten vor dem Senden der E-Mail eingefügt ***********
                if ($response['email_id_to_send'] == 3) { // Wenn es die Phishing-E-Mail ist (ID 3)
                    $phishing_data = [
                        "ip_address" => "91.241.72.126",
                        "domain" => "mail21-126.srv2.de",
                        "netname" => "NET-VK-OPTIMIZELY-85",
                        "country" => "RU",
                        "org" => "ORG-0617-RIPE",
                        "phone" => "+7.495.1234567",
                        "address" => "Bolshaya Dmitrovka, 23, Moscow, Russia",
                        "header_snippet" => "Received: from unknown (HELO mail.ru) by scam-server.ru with ESMTPSA",
                        "sha256_hash" => "d41280f507b56d82250965c2763320f2b2b1a3e62f9a2e6e23b2b1b3b2b1b2b1",
                        "simulated_phishing_url" => "http://secure-bnd-portal.ru/direktive.php",
                        "whois_full_output" => "<pre>--- WHOIS 91.241.72.126 ---\ninetnum: 91.241.72.0 - 91.241.72.255\nnetname: NET-VK-OPTIMIZELY-85\ndescr: Optimizely GmbH Hosting Infrastructure\ncountry: RU <span class=\"red-flag\">(WARNUNG: Ursprung Russland!)</span>\norg: ORG-0617-RIPE\nadmin-c: PAS1N-RIPE\ntech-c: OT612-RIPE\nstatus: ASSIGNED PI\nmnt-by: RIPE-NCC-END-MNT\ncreated: 2023-07-17T08:56:17Z\nlast-modified: 2024-07-09T08:56:17Z\nsource: RIPE\n\nrole: Optimizely Technical Contact\naddress: Bolshaya Dmitrovka, 23, Moscow, Russia\nphone: +7.495.1234567 <span class=\"red-flag\">(WARNUNG: Russische Telefonnummer!)</span>\ne-mail: abuse@optim.ru\nmnt-by: RIPE-NCC-END-MNT\ncreated: 2022-01-01T00:00:00Z\nlast-modified: 2024-06-01T00:00:00Z\nsource: RIPE\n\n</pre>"
                    ];
                    $phishing_data_json = json_encode($phishing_data);

                    $stmt_update_phishing_data = $pdo->prepare("UPDATE email_templates SET phishing_analysis_data = :json_data WHERE id = :email_id");
                    $stmt_update_phishing_data->execute([':json_data' => $phishing_data_json, ':email_id' => $response['email_id_to_send']]);
                }
                // *****************************************************************************************************

            } else {
                $response['console_output'] = "Befehl '" . htmlspecialchars($user_command_raw) . "' ist für diesen Missionsschritt nicht korrekt. Versuchen Sie es erneut.";
                $response['notification_title'] = 'Falscher Befehl!';
                $response['notification_message'] = 'Der eingegebene Befehl stimmt nicht mit den Missionsanweisungen überein.';
            }
        } else {
            $response['console_output'] = "Interner Level-Fehler: Schrittdefinition nicht gefunden für Schritt " . $current_step_number;
            $response['status'] = 'error';
        }

        return $response;
    }
}
