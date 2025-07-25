<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("Bitte einloggen.");
}
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$stmt_inbox = $pdo->prepare(
    "SELECT e.id, e.sent_at, e.is_read, et.subject, et.sender_name 
     FROM emails e JOIN email_templates et ON e.template_id = et.id 
     WHERE e.recipient_id = :user_id ORDER BY e.sent_at DESC"
);
$stmt_inbox->execute([':user_id' => $user_id]);
$inbox_emails = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

// Vollständige E-Mail-Daten (inklusive Body) für JavaScript bereitstellen
$full_emails_data = [];
$stmt_full = $pdo->prepare("SELECT et.body_html FROM emails e JOIN email_templates et ON e.template_id = et.id WHERE e.id = :id");
foreach ($inbox_emails as $mail) {
    $stmt_full->execute([':id' => $mail['id']]);
    $body = $stmt_full->fetchColumn();
    $full_emails_data[$mail['id']] = array_merge($mail, ['body_html' => $body]);
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Postfach</title>
    <style>
        html,
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            color: #e0e0e0;
            background-color: #1a1a1a;
            height: 100vh;
            overflow: hidden;
        }

        .mail-client {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .main-area {
            display: flex;
            flex-grow: 1;
            overflow: hidden;
        }

        .email-list {
            width: 350px;
            min-width: 250px;
            border-right: 1px solid #333;
            overflow-y: auto;
            background-color: #111;
        }

        .email-item {
            padding: 15px 10px;
            border-bottom: 1px solid #2a2a2a;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .email-item:hover {
            background-color: #2a2a2a;
        }

        .email-item.active {
            background-color: #3c3c3c;
            border-left-color: #00ff7f;
        }

        .email-display {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .email-body {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.6;
            color: #ccc;
        }

        .email-body a {
            color: #4CAF50;
            text-decoration: underline;
        }

        /* So leuchten die Titel ungelesener Mails, wie du wolltest */
        .email-item.unread {
            background-color: #1c251c;
            /* Leichter grüner Schimmer */
            border-left: 3px solid #00ff7f;
            /* Heller grüner Streifen */
        }

        .email-item.unread strong {
            color: #00ff7f;
            /* Titel und Absender leuchten grün */
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="mail-client">
        <div class="main-area">
            <div id="email-list-container" class="email-list">
                <?php if (empty($inbox_emails)): ?>
                    <p style="text-align: center; padding-top: 20px; color: #888;">Posteingang ist leer.</p>
                <?php else: ?>
                    <?php foreach ($inbox_emails as $email): ?>
                        <div class="email-item <?php if (!$email['is_read']) echo 'unread'; ?>" data-id="<?php echo $email['id']; ?>">
                            <div><strong>Von:</strong> <?php echo htmlspecialchars($email['sender_name']); ?></div>
                            <div><strong>Betreff:</strong> <?php echo htmlspecialchars($email['subject']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="email-display">
                <h2 id="display-subject">Wähle eine E-Mail aus</h2>
                <p><strong>Von:</strong> <span id="display-sender"></span></p>
                <hr style="border-color: #333;">
                <div id="display-body" class="email-body"></div>
            </div>
        </div>
    </div>

    <script>
        const allEmails = <?php echo json_encode($full_emails_data); ?>;


        // NEUE FUNKTION: Ruft den Desktop auf, um einen Link im In-Game-Browser zu öffnen
        function openInGameBrowser(url) {
            // Prüft, ob die Funktion im übergeordneten Fenster (desktop.php) existiert
            if (window.parent && typeof window.parent.openBrowserAndLoadUrl === 'function') {
                window.parent.openBrowserAndLoadUrl(url);
            } else {
                // Fallback: Öffnet einen neuen Tab, falls etwas schiefgeht
                console.error("Konnte den In-Game-Browser nicht erreichen.");
                window.open(url, '_blank');
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            const emailItems = document.querySelectorAll('.email-item');

            emailItems.forEach(item => {
                item.addEventListener('click', function() {
                    const emailId = this.dataset.id;
                    if (!allEmails[emailId]) return; // Sicherheitsprüfung

                    const emailData = allEmails[emailId];

                    // Inhalte im Anzeigefenster aktualisieren
                    document.getElementById('display-subject').textContent = emailData.subject;
                    document.getElementById('display-sender').textContent = emailData.sender_name;
                    document.getElementById('display-body').innerHTML = emailData.body_html;

                    // Visuelle Hervorhebung für das angeklickte Element
                    emailItems.forEach(el => el.classList.remove('active'));
                    this.classList.add('active');

                    // Wenn die E-Mail ungelesen war, markiere sie als gelesen
                    if (this.classList.contains('unread')) {
                        // 1. Visuelle Markierung sofort entfernen
                        this.classList.remove('unread');

                        // 2. Status an den Server senden
                        const formData = new FormData();
                        formData.append('id', emailId);
                        fetch('api_mark_as_read.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    // 3. Dem Desktop SOFORT befehlen, den Zähler neu zu berechnen
                                    if (window.parent && window.parent.forceEmailUpdate) {
                                        window.parent.forceEmailUpdate();
                                    }
                                }
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>