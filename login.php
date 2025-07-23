<?php
session_start();

// Einbinden der Datenbankverbindung. require_once ist sicherer.
require_once 'db_connect.php';

// Prüfen, ob die Datenbankverbindung überhaupt hergestellt wurde.
if (!isset($pdo)) {
    die("Kritischer Fehler: Die Datenbankverbindung konnte nicht in db_connect.php hergestellt werden.");
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = 'Beide Felder sind erforderlich.';
    } else {
        $sql = "SELECT id, username, password_hash FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Passwort ist korrekt, Session starten
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // --- Logik zum Erstellen der Willkommens-E-Mail ---
            $user_id = $user['id'];

            // 1. Prüfen, ob der User die Willkommens-Mail schon hat
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE recipient_id = :user_id AND sender_id = 100");
            $stmt_check->execute([':user_id' => $user_id]);
            $email_exists = $stmt_check->fetchColumn();

            if ($email_exists == 0) {
                // 2. Wenn nicht, Willkommens-Mail erstellen
                $sql_welcome = "INSERT INTO emails (recipient_id, sender_id, sender_name, sender_email, subject, body_html, sent_at)
                                VALUES (:user_id, 100, 'Ausbilder Schmidt', 'schmidt@bnd.de', 'Willkommen beim BND, Rekrut!', 'Willkommen bei der Cyber-Abwehr. Ihre erste Aufgabe wartet auf Sie. Machen Sie sich mit dem System vertraut. Weitere Anweisungen folgen in Kürze.', NOW())";
                $pdo->prepare($sql_welcome)->execute([':user_id' => $user_id]);

                // 3. Erste Mission für den Spieler in der Progress-Tabelle anlegen
                $sql_mission = "INSERT INTO mission_progress (user_id, mission_id, current_step) VALUES (:user_id, 1, 1)";
                $pdo->prepare($sql_mission)->execute([':user_id' => $user_id]);
            }
            // --- Ende der E-Mail-Logik ---

            // KORREKTUR: Weiterleitung zur desktop.php
            header("Location: desktop.php");
            exit;
        } else {
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