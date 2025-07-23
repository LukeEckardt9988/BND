<?php
require 'db_connect.php'; // Stellen Sie sicher, dass dies der korrekte Pfad zu Ihrer DB-Verbindung ist

try {
    // Definieren Sie die E-Mail-Daten als PHP-Array
    $emails_to_insert = [
        // NEU: E-Mail für Level 1 - Netzwerk-Sicherheitscheck
        [
            'sender_name' => 'BND Missionsleitung',
            'sender_email' => 'mission@bnd.bund.de',
            'subject' => 'Neue Mission: Netzwerk-Sicherheitscheck (Level 1)',
            'body_html' => 'Sehr geehrte/r Rekrut/in,<br>Ihre erste operative Mission wartet auf Sie! Es geht darum, das interne Test-Netzwerk auf unautorisierte Aktivitäten zu überprüfen.<br><br>Dies ist eine grundlegende Aufgabe, um Ihre Fähigkeiten im Umgang mit unseren Standard-Tools zu testen. Denken Sie daran, dass Präzision und Aufmerksamkeit entscheidend sind.<br><br>Um die Mission zu starten, klicken Sie bitte auf den folgenden Link:<br><a href="level1.php">MISSION STARTEN: Netzwerk-Sicherheitscheck</a><br><br>Viel Erfolg,<br>Ihre BND Missionsleitung',
            'is_phishing' => FALSE,
            'target_level_id' => 1, // Angenommen, Level 1 ist die ID für 'Netzwerk-Sicherheitscheck'
            'phishing_analysis_data' => NULL
        ],
        // Bestehende E-Mail 1: Willkommensmail für Auszubildende vom BND
        [
            'sender_name' => 'BND Training',
            'sender_email' => 'training@bnd.bund.de',
            'subject' => 'Willkommen beim BND-Cyber-Training',
            'body_html' => 'Sehr geehrte/r Rekrut/in,<br>Herzlich willkommen beim Bundesnachrichtendienst! Wir freuen uns, Sie in unserem Team für das Cyber-Training begrüßen zu dürfen.<br><br>Ihr Training beginnt in Kürze. Bitte bereiten Sie sich auf spannende und herausfordernde Simulationen vor, die Sie auf reale Bedrohungen vorbereiten werden.<br><br>Für erste Informationen und Zugang zu den Grundlagendokumenten, besuchen Sie bitte unser internes Portal:<br><a href="javascript:void(0);" onclick="alert(\'Dies wäre Ihr internes BND Portal!\');">https://internes-bnd-portal.bnd.bund.de</a><br><br>Mit freundlichen Grüßen,<br>Ihr BND Trainings-Team',
            'is_phishing' => FALSE,
            'target_level_id' => NULL,
            'phishing_analysis_data' => NULL
        ],
        // Bestehende E-Mail 2: Geheimes Login-Passwort (simuliert)
        [
            'sender_name' => 'HR Abteilung',
            'sender_email' => 'hr@bnd.bund.de',
            'subject' => 'Ihr geheimer Login für das Trainingsportal',
            'body_html' => 'Guten Tag Rekrut/in,<br>dies ist eine automatisierte Nachricht zur Bereitstellung Ihres temporären Logins für das interne Trainingsportal des BND.<br><br>Ihr Benutzername: rekrut-[USERNAME]<br>Ihr temporäres Passwort: <span style="color:#ff6b6b; font-weight:bold;">CyB3rTr@in!ng2025</span><br><br>Bitte ändern Sie dieses Passwort umgehend nach Ihrem ersten Login. Speichern Sie es nicht auf Ihrem lokalen Rechner und teilen Sie es niemandem mit.<br><br>Mit freundlichen Grüßen,<br>Ihre HR Abteilung des BND',
            'is_phishing' => FALSE,
            'target_level_id' => NULL,
            'phishing_analysis_data' => NULL
        ],
        // Bestehende E-Mail 3: Phishing-E-Mail vom Bundeskanzleramt mit Link zu Level 2
        [
            'sender_name' => 'Bundeskanzleramt',
            'sender_email' => 'kanzler@bundeskanzleramt.de',
            'subject' => 'Wichtige Anweisung vom Bundeskanzleramt',
            'body_html' => 'Sehr geehrte/r Mitarbeiter/in des BND,<br>im Rahmen der aktuellen Sicherheitslage erhalten Sie hiermit eine streng geheime Direktive des Bundeskanzlers persönlich.<br><br>Es ist absolut entscheidend, dass Sie das beigefügte Dokument umgehend herunterladen und die Anweisungen befolgen. Es enthält kritische Informationen zur Verteidigung unserer nationalen Infrastruktur.<br><br><a href="level2_phishing.php" class="phishing-link">Download: STRENG_HEIMLICHE_DIREKTIVE_KANzler.pdf</a><br><br>Diese Maßnahme ist nicht verhandelbar. Ihre umgehende Kooperation ist für die Sicherheit unseres Landes von größter Bedeutung.<br><br>Hochachtungsvoll,<br>Im Auftrag des Bundeskanzlers der Bundesrepublik Deutschland',
            'is_phishing' => TRUE,
            'target_level_id' => 2, // Angenommen, Level 2 ist die ID für 'Phishing-Analyse'
            'phishing_analysis_data' => [
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
                "whois_full_output" => "<pre>--- WHOIS 91.241.72.126 ---\ninetnum:        91.241.72.0 - 91.241.72.255\nnetname:        NET-VK-OPTIMIZELY-85\ndescr:          Optimizely GmbH Hosting Infrastructure\ncountry:        RU <span class=\"red-flag\">(WARNUNG: Ursprung Russland!)</span>\norg:            ORG-0617-RIPE\nadmin-c:        PAS1N-RIPE\ntech-c:         OT612-RIPE\nstatus:         ASSIGNED PI\nmnt-by:         RIPE-NCC-END-MNT\ncreated:        2023-07-17T08:56:17Z\nlast-modified:  2024-07-09T08:56:17Z\nsource:         RIPE\n\nrole:           Optimizely Technical Contact\naddress:        Bolshaya Dmitrovka, 23, Moscow, Russia\nphone:          +7.495.1234567 <span class=\"red-flag\">(WARNUNG: Russische Telefonnummer!)</span>\ne-mail:         abuse@optim.ru\nmnt-by:         RIPE-NCC-END-MNT\ncreated:        2022-01-01T00:00:00Z\nlast-modified:  2024-06-01T00:00:00Z\nsource:         RIPE\n\n</pre>"
            ]
        ]
    ];

    $sql = "INSERT INTO emails (sender_name, sender_email, subject, body_html, sent_at, is_phishing, target_level_id, phishing_analysis_data)
            VALUES (:sender_name, :sender_email, :subject, :body_html, :sent_at, :is_phishing, :target_level_id, :phishing_analysis_data)";
    $stmt = $pdo->prepare($sql);

    // Vor dem Einfügen prüfen, ob E-Mails bereits existieren könnten
    $check_sql = "SELECT COUNT(*) FROM emails WHERE subject = :subject AND sender_email = :sender_email";
    $check_stmt = $pdo->prepare($check_sql);


    foreach ($emails_to_insert as $email) {
        $check_stmt->execute([
            ':subject' => $email['subject'],
            ':sender_email' => $email['sender_email']
        ]);
        $count = $check_stmt->fetchColumn();

        if ($count == 0) { // Nur einfügen, wenn E-Mail noch nicht existiert
            // Konvertiere PHP-Array zu JSON String, wenn Daten vorhanden sind
            $phishing_data_json = $email['phishing_analysis_data'] !== NULL ? json_encode($email['phishing_analysis_data']) : NULL;

            $stmt->execute([
                ':sender_name' => $email['sender_name'],
                ':sender_email' => $email['sender_email'],
                ':subject' => $email['subject'],
                ':body_html' => $email['body_html'],
                ':sent_at' => date('Y-m-d H:i:s'), // Aktuelles Datum und Uhrzeit
                ':is_phishing' => $email['is_phishing'],
                ':target_level_id' => $email['target_level_id'],
                ':phishing_analysis_data' => $phishing_data_json
            ]);
            echo "E-Mail '" . $email['subject'] . "' erfolgreich eingefügt.<br>";
        } else {
            echo "E-Mail '" . $email['subject'] . "' existiert bereits, übersprungen.<br>";
        }
    }

    echo "Alle E-Mails erfolgreich verarbeitet!<br><br>";

    // Levels ebenfalls einfügen, falls noch nicht geschehen (bereinigt und mit Duplikatsprüfung)
    $levels_to_insert = [
        ['id' => 1, 'name' => 'Netzwerk-Sicherheitscheck', 'description' => 'Lerne, Netzwerke mit Nmap zu scannen.', 'entry_point_file' => 'level1.php'],
        ['id' => 2, 'name' => 'Phishing-Analyse', 'description' => 'Erkenne und analysiere Phishing-Versuche.', 'entry_point_file' => 'level2_phishing.php']
    ];

    $sql_levels = "INSERT INTO levels (id, name, description, entry_point_file) VALUES (:id, :name, :description, :entry_point_file)";
    $stmt_levels = $pdo->prepare($sql_levels);

    $check_level_sql = "SELECT COUNT(*) FROM levels WHERE id = :id"; // Prüfen nach ID, da ID fest vergeben ist
    $check_level_stmt = $pdo->prepare($check_level_sql);

    foreach ($levels_to_insert as $level) {
        $check_level_stmt->execute([':id' => $level['id']]);
        $count_level = $check_level_stmt->fetchColumn();

        if ($count_level == 0) {
            $stmt_levels->execute([
                ':id' => $level['id'],
                ':name' => $level['name'],
                ':description' => $level['description'],
                ':entry_point_file' => $level['entry_point_file']
            ]);
            echo "Level '" . $level['name'] . "' erfolgreich eingefügt.<br>";
        } else {
            echo "Level '" . $level['name'] . "' existiert bereits, übersprungen.<br>";
        }
    }
} catch (PDOException $e) {
    echo "Fehler beim Einfügen/Verarbeiten der Datenbankeinträge: " . $e->getMessage();
}
