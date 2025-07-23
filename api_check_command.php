<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    exit(json_encode(['status' => 'error', 'message' => 'Nicht autorisiert.']));
}

require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$user_command_raw = trim($_POST['command']);

$response = ['status' => 'no_action', 'message' => ''];

try {
    // 1. Hole den aktuellen Fortschritt des Spielers für die aktive Mission
    $stmt_progress = $pdo->prepare("SELECT mp.id, mp.mission_id, mp.current_step, l.name as level_name
                                    FROM mission_progress mp
                                    JOIN levels l ON mp.mission_id = l.id
                                    WHERE mp.user_id = :user_id AND mp.is_completed = 0 LIMIT 1");
    $stmt_progress->execute([':user_id' => $user_id]);
    $progress = $stmt_progress->fetch(PDO::FETCH_ASSOC);

    if (!$progress) {
        // Keine aktive Mission für den Spieler, daher kein spezifischer Befehl zu prüfen
        exit(json_encode(['status' => 'no_action', 'message' => 'Keine aktive Mission gefunden.']));
    }

    // 2. Hole die Daten für den erwarteten Schritt aus der mission_steps Tabelle
    $stmt_step = $pdo->prepare("SELECT * FROM mission_steps WHERE mission_id = :mission_id AND step_number = :step_number");
    $stmt_step->execute([
        'mission_id' => $progress['mission_id'],
        'step_number' => $progress['current_step']
    ]);
    $step_data = $stmt_step->fetch(PDO::FETCH_ASSOC);

    if (!$step_data) {
        // Aktueller Schritt ist in mission_steps nicht definiert, dies ist ein Fehler in der Spiellogik-Konfiguration
        exit(json_encode(['status' => 'error', 'message' => 'Missionsschritt nicht definiert.']));
    }

    // Bereite den erwarteten Befehl für den Vergleich vor
    $expected_full_command = $step_data['expected_command_keyword'];
    if (!empty($step_data['expected_command_argument'])) {
        $expected_full_command .= ' ' . $step_data['expected_command_argument'];
    }

    // Vergleiche den Befehl des Spielers mit dem erwarteten Befehl
    if ($user_command_raw === $expected_full_command) {
        // ERFOLG! Der Spieler hat den richtigen Befehl für diesen Schritt eingegeben.

        $pdo->beginTransaction(); // Starte Transaktion für Atomizität

        // Aktualisiere den Fortschritt des Spielers
        if ($step_data['next_step_id'] !== null) {
            // Gehe zum nächsten Schritt
            $stmt_update = $pdo->prepare("UPDATE mission_progress SET current_step = :next_step WHERE id = :id");
            $stmt_update->execute(['next_step' => $step_data['next_step_id'], 'id' => $progress['id']]);
        } else {
            // Kein nächster Schritt -> Mission ist beendet
            $stmt_complete = $pdo->prepare("UPDATE mission_progress SET is_completed = 1 WHERE id = :id");
            $stmt_complete->execute(['id' => $progress['id']]);

            // Achievement für den Abschluss von Level 1 vergeben, falls zutreffend
            if ($progress['mission_id'] == 1) { // Angenommen Mission 1 ist Level 1
                $achievement_id = 1; // 'Netzwerk-Pionier'
                $stmt_check_achievement = $pdo->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id = :user_id AND achievement_id = :achievement_id");
                $stmt_check_achievement->execute([':user_id' => $user_id, ':achievement_id' => $achievement_id]);
                if ($stmt_check_achievement->fetchColumn() == 0) {
                    $stmt_award_achievement = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (:user_id, :achievement_id)");
                    $stmt_award_achievement->execute([':user_id' => $user_id, ':achievement_id' => $achievement_id]);
                    // Achievement-Benachrichtigung hinzufügen
                    $response['achievement'] = [
                        'title' => 'Achievement freigeschaltet!',
                        'message' => '<strong>Netzwerk-Pionier:</strong> Du hast deine erste Mission erfolgreich abgeschlossen und das Netzwerk gesichert!'
                    ];
                }
            }
        }

        // Eine E-Mail auslösen, falls für diesen Schritt angegeben
        if ($step_data['email_on_complete_id'] !== null) {
            // Aktualisiere recipient_id, um E-Mail-Zustellung an den aktuellen Benutzer auszulösen
            $stmt_send_mail = $pdo->prepare("UPDATE emails SET recipient_id = :user_id, is_read = 0 WHERE id = :email_id");
            $stmt_send_mail->execute(['user_id' => $user_id, 'email_id' => $step_data['email_on_complete_id']]);
        }

        // Sende eine Benachrichtigung an den Spieler zurück
        if ($step_data['notification_title'] || $step_data['notification_message']) {
            $response['status'] = 'show_notification';
            $response['data'] = [
                'title' => $step_data['notification_title'] ?? 'System-Meldung',
                'message' => $step_data['notification_message'] ?? 'Aktion erfolgreich!'
            ];
        } else {
            $response['status'] = 'success'; // Generischer Erfolg, falls keine spezifische Benachrichtigung
        }

        // Ein Programm freischalten, falls für diesen Schritt angegeben
        if ($step_data['unlocks_program_id'] !== null) {
            $stmt_program = $pdo->prepare("SELECT name, description FROM programs WHERE id = :program_id");
            $stmt_program->execute([':program_id' => $step_data['unlocks_program_id']]);
            $unlocked_program_info = $stmt_program->fetch(PDO::FETCH_ASSOC);
            $response['unlocked_program'] = $unlocked_program_info;
        }

        $pdo->commit(); // Transaktion bestätigen
        echo json_encode($response);
    } else {
        // Spieler hat den falschen Befehl für den aktuellen Schritt eingegeben, tue nichts.
        echo json_encode(['status' => 'no_action']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Datenbankfehler in api_check_command.php: " . $e->getMessage());
    exit(json_encode(['status' => 'error', 'message' => 'Interner Serverfehler.']));
}
