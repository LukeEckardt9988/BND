<?php
// BND/game_logic/level2.php
// Dies ist ein reines Backend-Logik-Skript, das von api_game_command.php aufgerufen wird.
// Es sollte KEINE HTML-Ausgabe direkt erzeugen.

// Diese Funktion wird von api_game_command.php aufgerufen, um die Definitionen abzurufen.
// Sie muss außerhalb von handle_level_command definiert sein, damit sie direkt aufgerufen werden kann.
function get_level_step_definition($step_number_param)
{
    $level_steps = [
        1 => [ // Step 1: Analyze Email Header
            'expected_command' => 'analyze_email_header 3', // Assuming Email ID 3 is the phishing email
            'console_output' => "Simulierter E-Mail-Header für E-Mail ID 3:\n\nReceived: from mail21-126.srv2.de (91.241.72.126) by bnd-mailserver.de\n        (Postfix) with ESMTPSA id ABCDEF0123456; Thu, 24 Jul 2025 09:00:00 +0200\nReceived: from unknown (HELO mail.ru) by scam-server.ru with ESMTPSA\n        id ZYXWVUTSRQPONMLKJ; Thu, 24 Jul 2025 08:59:00 +0300\nFrom: \"Bundeskanzleramt\" <kanzler@bundeskanzleramt.de>\nTo: \"Rekrut BND\" <rekrut@bnd.de>\nSubject: Wichtige Anweisung vom Bundeskanzleramt\nX-Originating-IP: [91.241.72.126]\nMessage-ID: <unique-id-for-this-email@bundeskanzleramt.de>\n\n[Inhalt der E-Mail würde hier folgen]",
            'notification_title' => 'Header analysiert!',
            'notification_message' => 'Sie haben den Header erfolgreich analysiert. Notieren Sie sich die verdächtige Ursprungs-IP-Adresse. Führen Sie nun eine WHOIS-Abfrage darauf durch.',
            'email_id_to_send' => null, // No email sent after this step
            'program_to_unlock_id' => 3, // Assuming 'analyze_email' program ID is 3
            'next_step_id' => 2, // Proceed to step 2 (WHOIS)
            'description_for_display' => 'Analysieren Sie den E-Mail-Header der Phishing-E-Mail (ID 3) um die Absender-IP zu finden. Befehl: analyze_email_header 3'
        ],
        2 => [ // Step 2: WHOIS Lookup
            'expected_command' => 'whois 91.241.72.126',
            'console_output' => "<pre>--- WHOIS 91.241.72.126 ---\ninetnum: 91.241.72.0 - 91.241.72.255\nnetname: NET-VK-OPTIMIZELY-85\ndescr: Optimizely GmbH Hosting Infrastructure\ncountry: RU <span class=\"red-flag\">(WARNUNG: Ursprung Russland!)</span>\norg: ORG-0617-RIPE\nadmin-c: PAS1N-RIPE\ntech-c: OT612-RIPE\nstatus: ASSIGNED PI\nmnt-by: RIPE-NCC-END-MNT\ncreated: 2023-07-17T08:56:17Z\nlast-modified: 2024-07-09T08:56:17Z\nsource: RIPE\n\nrole: Optimizely Technical Contact\naddress: Bolshaya Dmitrovka, 23, Moscow, Russia\nphone: +7.495.1234567 <span class=\"red-flag\">(WARNUNG: Russische Telefonnummer!)</span>\ne-mail: abuse@optim.ru\nmnt-by: RIPE-NCC-END-MNT\ncreated: 2022-01-01T00:00:00Z\nlast-modified: 2024-06-01T00:00:00Z\nsource: RIPE\n\n</pre>",
            'notification_title' => 'WHOIS-Ergebnis erhalten!',
            'notification_message' => 'Die WHOIS-Abfrage hat interessante Details zur Herkunft der IP-Adresse geliefert. Melden Sie die IP-Adresse als Phishing-Quelle.',
            'email_id_to_send' => null,
            'program_to_unlock_id' => 4, // Assuming 'whois' program ID is 4
            'next_step_id' => 3, // Proceed to step 3 (Report Phishing)
            'description_for_display' => 'Führen Sie eine WHOIS-Abfrage auf die verdächtige IP-Adresse (91.241.72.126) durch, um weitere Informationen zu erhalten. Befehl: whois 91.241.72.126'
        ],
        3 => [ // Step 3: Report Phishing
            'expected_command' => 'report_phishing_ip 91.241.72.126',
            'console_output' => 'IP-Adresse 91.241.72.126 als Phishing-Quelle gemeldet. Danke für Ihre Wachsamkeit!',
            'notification_title' => 'Phishing-Quelle gemeldet!',
            'notification_message' => 'Hervorragend, Rekrut! Sie haben die Phishing-Quelle korrekt identifiziert und gemeldet. Level 2 abgeschlossen!',
            'email_id_to_send' => null, // No email sent after this step
            'program_to_unlock_id' => 5, // Assuming 'report_phishing' program ID is 5
            'mission_completed' => true, // Marks mission as completed
            'next_step_id' => null, // No next step in this mission
            'description_for_display' => 'Phishing-Analyse erfolgreich abgeschlossen! Warten Sie auf neue Direktiven.'
        ]
    ];
    return $level_steps[$step_number_param] ?? null;
}


if (!function_exists('handle_level_command')) {
    function handle_level_command($user_id, $user_command_raw, $mission_id, $current_step_number, $pdo) {
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

        // Hole die Level-Schritt-Definitionen über die Hilfsfunktion
        $step_definition = get_level_step_definition($current_step_number);

        if ($step_definition) {
            // Prüfe, ob der eingegebene Befehl dem erwarteten Befehl entspricht
            if ($user_command_raw === $step_definition['expected_command']) {
                $response['success'] = true;
                $response['console_output'] = $step_definition['console_output'];
                $response['notification_title'] = $step_definition['notification_title'];
                $response['notification_message'] = $step_definition['notification_message'];
                $response['email_id_to_send'] = $step_definition['email_id_to_send'];
                $response['program_to_unlock_id'] = $step_definition['program_to_unlock_id'];
                $response['mission_completed'] = $step_definition['mission_completed'] ?? false;
                $response['next_step_id'] = $step_definition['next_step_id'];

                // Aktualisiere die Beschreibung im mission_steps, wenn der Schritt erfolgreich war.
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
                    // Wenn die Mission abgeschlossen ist, den letzten Schritt mit der Abschlussbeschreibung aktualisieren
                    $stmt_update_desc = $pdo->prepare("UPDATE mission_steps SET description = :desc WHERE mission_id = :mid AND step_number = :snum");
                    $stmt_update_desc->execute([
                        ':desc' => $step_definition['description_for_display'], // Letzte Beschreibung ist die Abschlussnachricht
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

                    $stmt_update_phishing_data = $pdo->prepare("UPDATE emails SET phishing_analysis_data = :json_data WHERE id = :email_id");
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