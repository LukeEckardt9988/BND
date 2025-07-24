<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../db_connect.php'; // Pfad anpassen, falls es direkt im Root liegt

// Hier könntest du spezifische Missions- oder Level-Daten laden
// basierend auf $_SESSION['user_id'] und der aktuellen Mission.
// Für Level 2 nehmen wir an, dass die Phishing-E-Mail die relevanten Daten bereits in der DB hat.

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>BND Phishing-Analyse</title>
    <link rel="stylesheet" href="../style.css"> <style>
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .phishing-info-box {
            background-color: #2a2a2a;
            border: 1px solid #ff4d4d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .phishing-info-box h2 {
            color: #ff4d4d;
        }
        .task-description {
            background-color: #111;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 5px;
        }
        .task-description h3 {
            color: #00ff7f;
        }
        .code-example {
            background-color: #0d0d0d;
            border: 1px solid #444;
            padding: 10px;
            margin-top: 10px;
            border-radius: 3px;
            font-family: 'Courier New', Courier, monospace;
            color: #00ff7f;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="phishing-info-box">
            <h2>🚨 Verdächtige E-Mail entdeckt! 🚨</h2>
            <p>Es scheint, als hätten Sie eine potenziell schädliche Phishing-E-Mail erhalten. Dies ist Teil Ihres Trainings im Umgang mit Cyberbedrohungen.</p>
            <p>Ihre Aufgabe ist es nun, diese E-Mail auf verdächtige Merkmale zu untersuchen. Achten Sie auf Absender, Links und technische Details.</p>
        </div>

        <div class="task-description">
            <h3>Ihre Mission: Phishing-Analyse</h3>
            <p>Analysieren Sie die verdächtige E-Mail im Posteingang. Konzentrieren Sie sich auf folgende Punkte:</p>
            <ul>
                <li>Den <strong>vollständigen E-Mail-Header</strong> auf Ungereimtheiten überprüfen.</li>
                <li>Die <strong>Herkunft der IP-Adresse</strong> der sendenden Server überprüfen (via WHOIS-Lookup).</li>
                <li>Den <strong>Link in der E-Mail</strong> genau untersuchen, ohne ihn anzuklicken!</li>
            </ul>
            <p>Nutzen Sie die Konsole, um simulierte Tools für die Analyse zu verwenden. Hier sind einige Befehle, die Sie ausprobieren können:</p>
            
            <p><strong>1. E-Mail-Header analysieren:</strong></p>
            <div class="code-example">
                analyze_email_header [E-Mail-ID]
            </div>
            <p><em>(Ersetzen Sie `[E-Mail-ID]` mit der ID der Phishing-E-Mail, die Sie analysieren wollen. Die ID finden Sie, indem Sie die E-Mail im Posteingang anklicken und auf Details achten – oder einfach die neueste E-Mail nehmen!)</em></p>
            
            <p><strong>2. WHOIS-Lookup durchführen:</strong></p>
            <div class="code-example">
                whois [IP-Adresse]
            </div>
            <p><em>(Nutzen Sie die IP-Adresse, die Sie aus dem E-Mail-Header erhalten haben.)</em></p>

            <p>Wenn Sie die verdächtige IP-Adresse gefunden haben, melden Sie diese im System.</p>
        </div>
    </div>
</body>
</html>