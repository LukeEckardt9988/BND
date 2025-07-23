<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<!DOCTYPE html><html><head><title>Nicht angemeldet</title><style>body { font-family: 'Segoe UI', sans-serif; background-color: #1a1a1a; color: #e0e0e0; padding: 20px; }</style></head><body><h1>Zugriff verweigert</h1><p>Bitte melde dich an, um deine Favoriten zu sehen.</p></body></html>";
    exit;
}

require '../db_connect.php'; // Pfad anpassen, da favorites.php im pages-Ordner liegt

$user_id = $_SESSION['user_id'];
$favorites = [];

try {
    $stmt = $pdo->prepare("SELECT title, url FROM user_favorites WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute([':user_id' => $user_id]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Fehler beim Laden deiner Favoriten. Bitte versuche es später erneut.";
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Meine Lesezeichen</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* ... (dein bestehendes CSS für favorites.php) ... */
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #0d0d0d;
            color: #e0e0e0;
            padding: 20px;
            margin: 0;
        }

        h1 {
            color: #00ff7f;
            border-bottom: 2px solid #00ff7f;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .favorites-list {
            list-style: none;
            padding: 0;
        }

        .favorites-list li {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .favorites-list li a {
            color: #00ff7f;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            flex-grow: 1;
        }

        .favorites-list li a:hover {
            text-decoration: underline;
        }

        .favorites-list li span {
            font-size: 0.9em;
            color: #aaa;
            margin-left: 20px;
        }

        p.empty-list {
            color: #aaa;
            font-style: italic;
        }

        p.error-message {
            color: #ff4d4d;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Meine Lesezeichen</h1>

    <?php if (isset($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif (empty($favorites)): ?>
        <p class="empty-list">Du hast noch keine Lesezeichen gespeichert.</p>
    <?php else: ?>
        <ul class="favorites-list">
            <?php foreach ($favorites as $fav): ?>
                <li>
                    <a href="#" onclick="loadInParentBrowser('<?php echo htmlspecialchars($fav['url']); ?>'); return false;">
                        <?php echo htmlspecialchars($fav['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <script>
        // Diese Funktion versucht, die loadUrl-Funktion des übergeordneten Browser-Frames aufzurufen.
        function loadInParentBrowser(url) {
            // window.parent ist der Browser.php-Frame
            if (window.parent && typeof window.parent.loadUrl === 'function') {
                window.parent.loadUrl(url, true); // Ruft loadUrl im Browser-Frame auf
            } else {
                // Fallback, wenn die Struktur unerwartet ist (sollte nicht passieren)
                console.error("Parent browser's loadUrl function not found. Loading directly in current iframe.");
                window.location.href = url; // Lädt den Link direkt in diesem (favorites.php) Iframe
            }
        }
    </script>
</body>

</html>