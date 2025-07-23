<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    echo json_encode(['status' => 'error', 'message' => 'Nicht autorisiert.']);
    exit;
}

require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$command = trim($_POST['command']);

// Hole den aktuellen Fortschritt des Spielers für Mission 1
$stmt = $pdo->prepare("SELECT * FROM mission_progress WHERE user_id = :user_id AND mission_id = 1");
$stmt->execute([':user_id' => $user_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Wenn der Spieler die Mission noch nicht gestartet hat, tue nichts
if (!$progress || $progress['is_completed']) {
    echo json_encode(['status' => 'no_action']);
    exit;
}

$notification = null;
$next_step = $progress['current_step'];

// Logik für Mission 1
if ($progress['mission_id'] == 1) {
    switch ($progress['current_step']) {
        case 1: // Erwartet den ersten Netzwerk-Scan
            if ($command === 'nmap -sn 10.2.2.0/24') {
                $notification = [
                    'title' => 'Missions-Update',
                    'message' => 'Gut gemacht, Rekrut. Eine der IPs in diesem Netz gehört hier nicht hin: <b>10.2.2.15</b>.<br><br>Führe einen detaillierten Port-Scan auf diesem Ziel durch, um mehr herauszufinden.'
                ];
                $next_step = 2;
            }
            break;

        case 2: // Erwartet den Port-Scan
            if ($command === 'nmap 10.2.2.15') {
                 $notification = [
                    'title' => 'Anomalie entdeckt!',
                    'message' => 'Ein verdächtiger Port (1337) ist offen. Das riecht nach einer Backdoor.<br><br>Nutze einen aggressiven Scan (<b>-A</b>), um alles über das System herauszufinden.'
                ];
                $next_step = 3;
            }
            break;
        
        // Hier können weitere Schritte folgen...
    }
}

// Wenn ein Schritt abgeschlossen wurde, update die Datenbank und sende die Benachrichtigung
if ($notification) {
    $stmt_update = $pdo->prepare("UPDATE mission_progress SET current_step = :next_step WHERE id = :id");
    $stmt_update->execute([':next_step' => $next_step, ':id' => $progress['id']]);
    echo json_encode(['status' => 'show_notification', 'data' => $notification]);
} else {
    echo json_encode(['status' => 'no_action']);
}