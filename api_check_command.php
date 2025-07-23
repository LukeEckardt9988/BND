<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    exit(json_encode(['status' => 'error']));
}

require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$command = trim($_POST['command']);

// 1. Hole den aktuellen Schritt des Spielers
$stmt_progress = $pdo->prepare("SELECT * FROM mission_progress WHERE user_id = :user_id AND is_completed = 0 LIMIT 1");
$stmt_progress->execute([':user_id' => $user_id]);
$progress = $stmt_progress->fetch(PDO::FETCH_ASSOC);

if (!$progress) {
    exit(json_encode(['status' => 'no_action']));
}

// 2. Hole die Daten für den erwarteten Schritt aus der Missions-Tabelle
$stmt_step = $pdo->prepare("SELECT * FROM mission_steps WHERE mission_id = :mission_id AND step_number = :step_number");
$stmt_step->execute(['mission_id' => $progress['mission_id'], 'step_number' => $progress['current_step']]);
$step_data = $stmt_step->fetch(PDO::FETCH_ASSOC);

if (!$step_data) {
    exit(json_encode(['status' => 'no_action']));
}

// 3. Vergleiche den Befehl des Spielers mit dem erwarteten Befehl
if ($command === $step_data['trigger_command']) {
    // ERFOLG! Der Spieler hat den richtigen Befehl eingegeben.

    // 4. Update den Fortschritt des Spielers
    if ($step_data['next_step_id'] !== null) {
        // Gehe zum nächsten Schritt
        $stmt_update = $pdo->prepare("UPDATE mission_progress SET current_step = :next_step WHERE id = :id");
        $stmt_update->execute(['next_step' => $step_data['next_step_id'], 'id' => $progress['id']]);
    } else {
        // Kein nächster Schritt -> Mission ist beendet
        $stmt_complete = $pdo->prepare("UPDATE mission_progress SET is_completed = 1 WHERE id = :id");
        $stmt_complete->execute(['id' => $progress['id']]);
    }

    // 5. Prüfe, ob eine E-Mail gesendet werden soll
    if ($step_data['email_on_complete_id'] !== null) {
        $stmt_send_mail = $pdo->prepare("UPDATE emails SET recipient_id = :user_id WHERE id = :email_id");
        $stmt_send_mail->execute(['user_id' => $user_id, 'email_id' => $step_data['email_on_complete_id']]);
    }

    // 6. Sende die Benachrichtigung an den Spieler zurück
    $notification = [
        'title' => $step_data['notification_title'],
        'message' => $step_data['notification_message']
    ];
    echo json_encode(['status' => 'show_notification', 'data' => $notification]);
} else {
    // Der Spieler hat den falschen Befehl eingegeben, tue nichts.
    echo json_encode(['status' => 'no_action']);
}
