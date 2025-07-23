<?php
session_start();
header('Content-Type: application/json');

// Prüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert. Bitte melde dich an.']);
    exit;
}

require 'db_connect.php'; // Stelle sicher, dass dies der korrekte Pfad zu deiner DB-Verbindung ist

// Daten aus dem POST-Request empfangen
$user_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$url = trim($_POST['url'] ?? '');

// Grundlegende Validierung
if (empty($title) || empty($url)) {
    echo json_encode(['success' => false, 'message' => 'Titel und URL dürfen nicht leer sein.']);
    exit;
}

// Favorit in die Datenbank einfügen
try {
    $stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, title, url) VALUES (:user_id, :title, :url)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $title,
        ':url' => $url
    ]);

    echo json_encode(['success' => true, 'message' => 'Favorit erfolgreich hinzugefügt!']);

} catch (PDOException $e) {
    // Optional: Fehler in Server-Log schreiben, NICHT direkt an den Benutzer ausgeben
    // error_log("Datenbankfehler beim Hinzufügen von Favoriten: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern des Favoriten.']);
}
?>