<?php
// Ein einfacher Browser, der HTML-Dateien aus dem 'pages'-Verzeichnis lädt.

// Sicherheits-Check: Nur erlaubte Zeichen im Dateinamen
$page = $_GET['page'] ?? 'start.html';
if (!preg_match('/^[a-zA-Z0-9_\-]+\.html$/', $page)) {
    die("Ungültiger Dateiname.");
}

$filePath = __DIR__ . '/pages/' . $page;

if (file_exists($filePath)) {
    // Lade und zeige den Inhalt der HTML-Datei an
    echo file_get_contents($filePath);
} else {
    // Zeige eine Fehlerseite, wenn die Datei nicht existiert
    echo "<h1>404 - Seite nicht gefunden</h1>";
    echo "<p>Die angeforderte Seite '<strong>" . htmlspecialchars($page) . "</strong>' konnte nicht gefunden werden.</p>";
}
?>