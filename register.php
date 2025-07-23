<?php
require 'db_connect.php';
$message = '';

// --- Hilfsfunktionen für simulierte Daten ---
function generate_gateway_ip() {
    $subnets = ['192.168.0.1', '192.168.1.1', '192.168.2.1', '192.168.178.1'];
    return $subnets[array_rand($subnets)];
}

function generate_user_agent() {
    $agents = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Safari/605.1.15"
    ];
    return $agents[array_rand($agents)];
}


// --- Formularverarbeitung ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // IP-Adresse auswählen (Logik von vorher)
    $chosen_ip = trim($_POST['ip_address']); // Du brauchst das Dropdown für die IP-Auswahl noch

    if (empty($username) || empty($password) || empty($chosen_ip)) {
        $message = 'Codename, Passwort und eine freie IP sind erforderlich.';
    } else {
        // E-Mail automatisch generieren
        $email = strtolower($username) . '@bnd.de';
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $pdo->beginTransaction();

            // 1. User erstellen
            $sql_user = "INSERT INTO users (username, email, password_hash, ip_address) VALUES (:username, :email, :password_hash, :ip)";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':ip' => $chosen_ip
            ]);
            $new_user_id = $pdo->lastInsertId();

            // 2. Zugehöriges Gerät mit simulierten Daten erstellen
            $sql_device = "INSERT INTO player_devices (user_id, hostname, gateway_ip, user_agent, ports) VALUES (:user_id, :hostname, :gateway, :agent, :ports)";
            $stmt_device = $pdo->prepare($sql_device);
            $stmt_device->execute([
                ':user_id' => $new_user_id,
                ':hostname' => strtolower($username) . '-pc',
                ':gateway' => generate_gateway_ip(),
                ':agent' => generate_user_agent(),
                ':ports' => '{"22":{"status":"closed"}, "80":{"status":"closed"}}' // Standard-Ports
            ]);
            $new_device_id = $pdo->lastInsertId();

            // 3. User-Tabelle mit der neuen Device-ID aktualisieren
            $sql_update = "UPDATE users SET device_id = :device_id WHERE id = :user_id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([':device_id' => $new_device_id, ':user_id' => $new_user_id]);

            $pdo->commit();
            $message = "Registrierung erfolgreich! Deine E-Mail-Adresse lautet: " . htmlspecialchars($email);

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $message = 'Fehler: Codename oder IP bereits vergeben.';
            } else {
                $message = 'Ein Fehler ist aufgetreten: ' . $e->getMessage();
            }
        }
    }
}

// Logik zum Finden freier IPs für das Dropdown
$stmt_ips = $pdo->query("SELECT ip_address FROM users WHERE ip_address IS NOT NULL");
$occupied_ips = $stmt_ips->fetchAll(PDO::FETCH_COLUMN);
$available_ips = [];
for ($i = 2; $i <= 254; $i++) {
    $current_ip = "10.0.10." . $i;
    if (!in_array($current_ip, $occupied_ips)) {
        $available_ips[] = $current_ip;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>BND Rekrutierung</title>
    </head>
<body>
    <div class="container">
        <h2>Neuen Agenten registrieren</h2>
        
        <div class="info-box" style="background-color:#f0f8ff; border-left: 4px solid #1e90ff; padding: 10px; margin-bottom: 20px;">
            <p><strong>Hinweis:</strong> Du benötigst nur einen Codenamen und ein Passwort.</p>
            <p>Deine offizielle Dienst-E-Mail wird automatisch aus deinem Codenamen generiert (z.B. <strong>codename@bnd.de</strong>) und ist für die Kommunikation im Spiel erforderlich.</p>
        </div>

        <?php if (!empty($message)) { echo "<p class='message'>" . htmlspecialchars($message) . "</p>"; } ?>
        <form action="register.php" method="post">
            <input type="text" name="username" placeholder="Codename" required>
            <input type="password" name="password" placeholder="Passwort" required>
            
            <label for="ip_address">Wähle deine System-IP im internen Netz:</label>
            <select name="ip_address" id="ip_address" required>
                <?php foreach ($available_ips as $ip): ?>
                    <option value="<?php echo htmlspecialchars($ip); ?>"><?php echo htmlspecialchars($ip); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Registrierung abschließen</button>
        </form>
    </div>
</body>
</html>