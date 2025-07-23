<?php
// Ändere diese Daten entsprechend deiner IONOS-Datenbank
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cia_game');

// Erstellt eine Datenbankverbindung mit PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Setzt den Fehlermodus von PDO auf Exception, um Fehler besser zu sehen
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Verhindert, dass Prepared Statements emuliert werden (wichtige Sicherheitseinstellung)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Gibt eine Fehlermeldung aus, wenn die Verbindung scheitert
    die("FEHLER: Konnte keine Verbindung zur Datenbank herstellen. " . $e->getMessage());
}
?>