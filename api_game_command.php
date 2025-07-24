<?php
session_start();
require_once 'db_connect.php';

// Nach der Entwicklung sollten error_reporting und display_errors entfernt oder deaktiviert werden.
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['console_output' => 'Nicht angemeldet. Bitte loggen Sie sich erneut ein.', 'status' => 'error']);
    exit();
}

$user_command_raw = trim($_POST['command'] ?? '');
$response = [
    'console_output' => '',
    'status' => 'no_action', // Kann in der Antwort geändert werden
    'notification' => null, // ['title' => '...', 'message' => '...']
    'email_sent' => false, // Boolean
    'unlocked_program' => null, // ['name' => '...', 'description' => '...']
    'reload_iframe_id' => null // Z.B. 'emails-window' oder 'browser-window'
];

try {
    // 1. Hole den aktuellen Missionsfortschritt des Spielers
    $stmt = $pdo->prepare("SELECT mission_id, current_step, status FROM mission_progress WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    $active_mission_id = $progress ? $progress['mission_id'] : null;
    $current_step_number = $progress ? $progress['current_step'] : null;
    $mission_status = $progress ? $progress['status'] : null;

    // --- LOGIK FÜR MISSIONSSTART (wenn keine aktive Mission vorhanden ist oder abgeschlossen ist) ---
    if (!$active_mission_id || $mission_status === 'completed') {
        if ($user_command_raw === 'start training') {
            $mission_id_to_start = 1; // Die ID der ersten Mission (Level 1)
            $first_step_number = 1; // Der erste Schritt in Level 1

            // Prüfen, ob die Mission 1 bereits als abgeschlossen markiert ist
            if ($mission_status === 'completed' && $active_mission_id == $mission_id_to_start) {
                $response['console_output'] = "Ihre Ausbildung ist bereits abgeschlossen. Warten Sie auf neue Anweisungen oder starten Sie ein neues Spiel.";
                echo json_encode($response);
                exit();
            }

            // Transaktion starten
            $pdo->beginTransaction();

            // Mission in mission_progress initialisieren/startent
            $stmt_check_progress_exists = $pdo->prepare("SELECT id FROM mission_progress WHERE user_id = :user_id");
            $stmt_check_progress_exists->execute([':user_id' => $user_id]);
            if ($stmt_check_progress_exists->fetch()) {
                $stmt_update_mission = $pdo->prepare("UPDATE mission_progress SET mission_id = :mission_id, current_step = :step, status = 'active', is_completed = 0 WHERE user_id = :user_id");
                $stmt_update_mission->execute([':mission_id' => $mission_id_to_start, ':step' => $first_step_number, ':user_id' => $user_id]);
            } else {
                $stmt_insert_mission = $pdo->prepare("INSERT INTO mission_progress (user_id, mission_id, current_step, status, is_completed) VALUES (:user_id, :mission_id, :step, 'active', 0)");
                $stmt_insert_mission->execute([':user_id' => $user_id, ':mission_id' => $mission_id_to_start, ':step' => $first_step_number]);
            }

            // Hole die Beschreibung für den ersten Schritt aus der Level-Logik
            require_once 'game_logic/level' . $mission_id_to_start . '.php'; // Level 1 Logik einbinden

            $first_step_definition = get_level_step_definition($first_step_number);
            $first_step_description = $first_step_definition['description_for_display'] ?? "Ihre erste Aufgabe wartet!";
            $first_step_next_id = $first_step_definition['next_step_id'] ?? NULL;

            // Den ersten Schritt in mission_steps hinzufügen/aktualisieren
            $stmt_insert_step_desc = $pdo->prepare("INSERT INTO mission_steps (mission_id, step_number, description, next_step_id) VALUES (:mid, :snum, :desc, :next_snum) ON DUPLICATE KEY UPDATE description = VALUES(description), next_step_id = VALUES(next_step_id)");
            $stmt_insert_step_desc->execute([
                ':mid' => $mission_id_to_start,
                ':snum' => $first_step_number,
                ':desc' => $first_step_description,
                ':next_snum' => $first_step_next_id
            ]);

            // E-Mail senden (Template ID 1)
            $template_id_to_send_on_start = 1;
            $stmt_get_template = $pdo->prepare("SELECT * FROM email_templates WHERE id = :template_id");
            $stmt_get_template->execute([':template_id' => $template_id_to_send_on_start]);
            $template_data = $stmt_get_template->fetch(PDO::FETCH_ASSOC);

            if ($template_data) {
                $stmt_insert_email = $pdo->prepare("INSERT INTO emails (template_id, recipient_id, sent_at, is_read, is_phishing_copy, phishing_analysis_data_copy)
                                                    VALUES (:template_id, :user_id, NOW(), 0, :is_phishing, :phishing_data)");
                $stmt_insert_email->execute([
                    ':template_id' => $template_data['id'],
                    ':user_id' => $user_id,
                    ':is_phishing' => $template_data['is_phishing'],
                    ':phishing_data' => $template_data['phishing_analysis_data']
                ]);
                $response['email_sent'] = true;
            } else {
                error_log("ERROR: api_game_command.php - Email template ID " . $template_id_to_send_on_start . " not found for sending on start.");
            }

            $response['console_output'] = "Training gestartet! Ihre erste Aufgabe: " . htmlspecialchars($first_step_description);
            $response['status'] = 'success';
            $response['notification'] = ['title' => 'Mission gestartet!', 'message' => 'Ihre Ausbildung beim BND hat begonnen. Überprüfen Sie Ihre E-Mails für das erste Briefing!'];
            $response['reload_iframe_id'] = 'emails-window';

            $pdo->commit(); // Transaktion abschließen
            echo json_encode($response);
            exit;
        } else {
            // Befehl außerhalb einer Mission
            $parts = explode(' ', $user_command_raw);
            $program_name = strtolower($parts[0]);
            if ($program_name === 'clear') {
                $response['console_output'] = "Konsole geleert.";
                $response['status'] = 'success';
            } else if ($program_name === 'help') {
                $response['console_output'] = "Verfügbare Befehle:\n  clear - Konsole leeren\n  help - Diese Hilfe anzeigen\n\nIhre Mission wird initialisiert, wenn Sie 'start training' eingeben.";
                $response['status'] = 'success';
            } else {
                $response['console_output'] = "Befehl '" . htmlspecialchars($user_command_raw) . "' nicht erkannt oder keine Befugnis außerhalb einer aktiven Mission. Geben Sie 'start training' ein, um zu beginnen.";
                $response['status'] = 'no_permission';
            }
            echo json_encode($response);
            exit;
        }
    }

    // --- LOGIK FÜR AKTIVE MISSION ---
    $level_logic_file = 'game_logic/level' . $active_mission_id . '.php';

    if (file_exists($level_logic_file)) {
        require_once $level_logic_file;

        if (function_exists('handle_level_command')) {
            $pdo->beginTransaction(); // Transaktion für Level-Logik

            // Aufruf der handle_level_command Funktion im Level-Skript
            $level_response = handle_level_command($user_id, $user_command_raw, $active_mission_id, $current_step_number, $pdo);

            // Ergebnisse der Level-Logik in die Haupt-Response integrieren
            $response = array_merge($response, $level_response);

            // Nach erfolgreicher Level-Logik: Fortschritt in mission_progress aktualisieren
            if ($response['success']) {
                if ($response['mission_completed']) {
                    $stmt_update_progress = $pdo->prepare("UPDATE mission_progress SET status = 'completed', current_step = :current_step WHERE user_id = :user_id AND mission_id = :mission_id");
                    $stmt_update_progress->execute([
                        ':current_step' => $current_step_number, // Letzter Schritt der Mission
                        ':user_id' => $user_id,
                        ':mission_id' => $active_mission_id
                    ]);
                } elseif ($response['next_step_id'] !== null) {
                    $stmt_update_progress = $pdo->prepare("UPDATE mission_progress SET current_step = :next_step WHERE user_id = :user_id AND mission_id = :mission_id");
                    $stmt_update_progress->execute([
                        ':next_step' => $response['next_step_id'],
                        ':user_id' => $user_id,
                        ':mission_id' => $active_mission_id
                    ]);
                }

                // E-Mail senden, falls in Level-Logik angefordert
                if ($response['email_id_to_send']) {
                    $template_id_to_send = $response['email_id_to_send'];

                    // Hole E-Mail-Template-Details
                    $stmt_get_template = $pdo->prepare("SELECT * FROM email_templates WHERE id = :template_id");
                    $stmt_get_template->execute([':template_id' => $template_id_to_send]);
                    $template_data = $stmt_get_template->fetch(PDO::FETCH_ASSOC);

                    if ($template_data) {
                        // Füge E-Mail als neue Zeile in die 'emails'-Tabelle ein
                        $stmt_insert_email = $pdo->prepare("INSERT INTO emails (template_id, recipient_id, sent_at, is_read, is_phishing_copy, phishing_analysis_data_copy)
                                                            VALUES (:template_id, :user_id, NOW(), 0, :is_phishing, :phishing_data)");
                        $stmt_insert_email->execute([
                            ':template_id' => $template_data['id'],
                            ':user_id' => $user_id,
                            ':is_phishing' => $template_data['is_phishing'],
                            ':phishing_data' => $template_data['phishing_analysis_data']
                        ]);
                        $response['email_sent'] = true; // Signal an Frontend, um E-Mail-Frame neu zu laden
                    } else {
                        error_log("ERROR: api_game_command.php - Email template ID " . $template_id_to_send . " not found for sending during mission.");
                    }
                }

                // Programm freischalten, falls in Level-Logik angefordert
                if ($response['program_to_unlock_id']) {
                    $stmt_program_info = $pdo->prepare("SELECT name, description FROM programs WHERE id = :program_id");
                    $stmt_program_info->execute([':program_id' => $response['program_to_unlock_id']]);
                    $unlocked_program = $stmt_program_info->fetch(PDO::FETCH_ASSOC);
                    if ($unlocked_program) {
                        $response['unlocked_program'] = $unlocked_program;
                    }
                }
                $response['reload_iframe_id'] = 'emails-window'; // Standardmäßig Emails neu laden bei Fortschritt
            }
            $pdo->commit();
        } else {
            $response['console_output'] = "Systemfehler: Level-Logik-Funktion 'handle_level_command' nicht gefunden in '" . $level_logic_file . "'.";
            $response['status'] = 'error';
        }
    } else {
        $response['console_output'] = "Systemfehler: Level-Logik-Datei '" . $level_logic_file . "' nicht gefunden für Mission ID " . $active_mission_id . ".";
        $response['status'] = 'error';
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERROR: api_game_command.php - Database error: " . $e->getMessage());
    $response = ['console_output' => 'Interner Serverfehler (DB-Fehler): ' . $e->getMessage(), 'status' => 'error', 'notification' => ['title' => 'Fehler', 'message' => 'Ein Datenbankfehler ist aufgetreten.']];
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERROR: api_game_command.php - General error: " . $e->getMessage());
    $response = ['console_output' => 'Interner Serverfehler (Allgemein): ' . $e->getMessage(), 'status' => 'error', 'notification' => ['title' => 'Fehler', 'message' => 'Ein unerwarteter Fehler ist aufgetreten.']];
}

echo json_encode($response);
exit();
