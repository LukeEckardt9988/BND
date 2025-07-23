<?php
session_start();

// Einbinden der Datenbankverbindung. require_once ist sicherer.
require_once 'db_connect.php';

// Prüfen, ob die Datenbankverbindung überhaupt hergestellt wurde.
if (!isset($pdo)) {
    die("Kritischer Fehler: Die Datenbankverbindung konnte nicht in db_connect.php hergestellt werden.");
}

$message = '';

// Prüft, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = 'Beide Felder sind erforderlich.';
    } else {
        // Schritt 1: Hole den User aus der Datenbank
        $sql = "SELECT id, username, password_hash FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Schritt 2: Überprüfe, ob der User existiert und das Passwort korrekt ist
        if ($user && password_verify($password, $user['password_hash'])) {
            // ERFOLG! Passwort ist korrekt.

            // Session starten
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // --- Logik zum Erstellen der ersten Mission ---
            $user_id = $user['id'];

            // Prüfen, ob der User die Mission 1 schon gestartet hat
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM mission_progress WHERE user_id = :user_id AND mission_id = 1");
            $stmt_check->execute([':user_id' => $user_id]);
            $mission_exists = $stmt_check->fetchColumn();

            if ($mission_exists == 0) {
                // Wenn nicht, erste Missions-Mail erstellen
                $body = "Willkommen bei der Cyber-Abwehr, Rekrut.\n\nIhre erste Aufgabe ist eine simple Aufklärungsmission. Wir haben verdächtige Aktivitäten in unserem Trainings-Netzwerk (10.2.2.0/24) festgestellt. Ihre Aufgabe ist es, sich einen Überblick zu verschaffen.\n\n**Ihr Befehl: `nmap -sn 10.2.2.0/24`**\n\nFühren Sie diesen Befehl in Ihrer Konsole aus und melden Sie alle Anomalien.\n\nSchmidt, Ausbilder";

                $sql_mission_mail = "INSERT INTO emails (recipient_id, sender_id, sender_name, sender_email, subject, body_html, sent_at)
                                     VALUES (:user_id, 100, 'Ausbilder Schmidt', 'schmidt@bnd.de', 'Erste Mission: Netzwerkaufklärung', :body, NOW())";
                $pdo->prepare($sql_mission_mail)->execute([':user_id' => $user_id, ':body' => $body]);

                // Erste Mission für den Spieler in der Progress-Tabelle anlegen
                $sql_mission_start = "INSERT INTO mission_progress (user_id, mission_id, current_step) VALUES (:user_id, 1, 1)";
                $pdo->prepare($sql_mission_start)->execute([':user_id' => $user_id]);
            }

            // Weiterleitung zum Desktop
            header("Location: desktop.php");
            exit;
        } else {
            // FEHLER! Falscher Codename oder falsches Passwort.
            $message = 'Falscher Codename oder falsches Passwort.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>BND Game - Login</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #2a2a2a;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            width: 350px;
        }

        input {
            display: block;
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #444;
            border-radius: 4px;
            background: #1a1a1a;
            color: white;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #00ff7f;
            color: #0d0d0d;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .error {
            margin-bottom: 1rem;
            color: #ff4d4d;
            text-align: center;
        }

        a {
            color: #00ff7f;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 style="text-align: center;">Agenten-Login</h2>
        <?php if (!empty($message)) {
            echo "<p class='error'>" . htmlspecialchars($message) . "</p>";
        } ?>
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="Codename" required>
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Einloggen</button>
        </form>
        <p style="text-align: center; font-size: 0.9em; margin-top: 1rem;">Noch kein Rekrut? <a href="register.php">Hier registrieren</a></p>
    </div>
</body>

</html>