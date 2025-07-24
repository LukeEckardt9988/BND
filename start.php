<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    // Wenn der Benutzer bereits angemeldet ist, direkt zum Desktop
    header("Location: desktop.php");
    exit;
}

// Wenn ein Gast die Startseite aufruft, wird er zum Login/Registrierung geleitet.
// Die erste Missions-E-Mail wird nach erfolgreichem Login/Registrierung zugewiesen.
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>BND Game - Willkommen</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .container {
            background: #2a2a2a;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            width: 450px;
        }
        h1 {
            color: #00ff7f;
        }
        p {
            margin-bottom: 1.5rem;
        }
        .button-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #00ff7f;
            color: #0d0d0d;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        .button-link:hover {
            background-color: #00e66a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Willkommen beim BND Cyber-Training</h1>
        <p>Betreten Sie die Welt der digitalen Spionage und Sicherheit. Sind Sie bereit, die Herausforderung anzunehmen?</p>
        <a href="login.php" class="button-link">Starten Sie Ihre Ausbildung</a>
    </div>
</body>
</html>