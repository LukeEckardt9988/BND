<?php
session_start();

require_once 'db_connect.php';

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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Missions- und E-Mail-Startlogik wurde verschoben!
            // Dies wird jetzt von start.php (für die allererste Email)
            // und api_game_command.php / levelX.php-Logik behandelt.

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
        <?php if (!empty($message)) { echo "<p class='error'>" . htmlspecialchars($message) . "</p>"; } ?>
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="Codename" required>
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Einloggen</button>
        </form>
        <p style="text-align: center; font-size: 0.9em; margin-top: 1rem;">Noch kein Rekrut? <a href="register.php">Hier registrieren</a></p>
    </div>
</body>
</html>