<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("Bitte einloggen.");
}
require 'db_connect.php'; // Stellen Sie sicher, dass db_connect.php korrekt eingebunden ist

$user_id = $_SESSION['user_id'];

try {
    // Posteingang abrufen: Join mit email_templates, um Betreff, Absender und Body zu bekommen
    // is_phishing und phishing_analysis_data kommen nun aus der 'emails' Tabelle selbst (als Kopien)
    $stmt_inbox = $pdo->prepare("SELECT e.id, e.sent_at, e.is_read, et.subject, et.sender_name, et.sender_email, et.body_html, e.is_phishing_copy AS is_phishing, e.phishing_analysis_data_copy AS phishing_analysis_data, 'inbox' as type FROM emails e JOIN email_templates et ON e.template_id = et.id WHERE e.recipient_id = :user_id ORDER BY e.sent_at DESC");
    $stmt_inbox->execute([':user_id' => $user_id]);
    $inbox_emails = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

    // Gesendete E-Mails abrufen (falls Sie die Sende-Funktion im Spiel verwenden wollen)
    // Diese Logik geht davon aus, dass in der 'emails' Tabelle der 'sender_id' des Spielers gespeichert ist,
    // wenn der Spieler selbst eine E-Mail sendet.
    // In der aktuellen Architektur senden Level-Skripte E-Mails von NPCs.
    // Daher ist dieser "Gesendet"-Teil hier im Kontext der Spiel-E-Mails weniger relevant und komplexer,
    // da wir keinen direkten 'sender_id' in der neuen 'emails' Tabelle haben, sondern nur 'recipient_id'.
    // Für eine vollständige "Gesendet"-Funktion müssten E-Mails, die der Spieler selbst sendet,
    // in einer separaten Logik verarbeitet und gespeichert werden.
    $sent_emails = []; // Temporär leer, da die Architektur keine Spieler-gesendeten E-Mails vorsieht.

} catch (PDOException $e) {
    error_log("Database error in emails.php: " . $e->getMessage());
    $inbox_emails = [];
    $sent_emails = [];
    // Hier könnten Sie eine Fehlermeldung an den Benutzer im HTML ausgeben
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

        .toolbar {
            padding: 10px;
            background: #2a2a2a;
            border-bottom: 1px solid #333;
            display: flex;
            gap: 10px;
        }

        .toolbar button {
            padding: 8px 15px;
            background-color: #444;
            color: white;
            border: 1px solid #555;
            cursor: pointer;
            border-radius: 3px;
        }

        .toolbar button.active {
            background-color: #00ff7f;
            color: #0d0d0d;
            border-color: #00ff7f;
        }

        .main-area {
            display: flex;
            flex-grow: 1;
            overflow: hidden;
        }

        .email-list {
            width: 350px;
            border-right: 1px solid #333;
            overflow-y: auto;
            background-color: #111;
        }

        .email-item {
            padding: 15px 10px;
            border-bottom: 1px solid #2a2a2a;
            cursor: pointer;
        }

        .email-item:hover,
        .email-item.active {
            background-color: #2a2a2a;
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

        .hidden {
            display: none !important;
        }

        #compose-modal {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1001;
        }

        #compose-form {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 15px black;
        }

        #compose-form input,
        #compose-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background: #1a1a1a;
            border: 1px solid #333;
            color: white;
            box-sizing: border-box;
        }

        #compose-form button {
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }
    </style>

</head>

<body>

    <div class="mail-client">
        <div class="toolbar">
            <button id="compose-btn">Neue E-Mail</button>
            <button id="inbox-btn" class="active">Posteingang</button>
            <button id="sent-btn">Gesendet</button>
        </div>
        <div class="main-area">
            <div id="email-list-container" class="email-list"></div>
            <div class="email-display">
                <h2 id="display-subject">Wähle eine E-Mail aus</h2>
                <p><strong id="display-label">Von:</strong> <span id="display-partner"></span></p>
                <hr style="border-color:#333;">
                <div id="display-body" class="email-body"></div>
            </div>
        </div>
    </div>

    <div id="compose-modal" class="hidden">
        <div id="compose-form">
            <h3>Neue Nachricht</h3>
            <input type="email" id="recipient-email" placeholder="An (z.B. admin@bnd.de)" required>
            <input type="text" id="subject-email" placeholder="Betreff" required>
            <textarea id="body-email" rows="10" placeholder="Deine Nachricht..."></textarea>
            <button id="send-mail-btn" style="background-color:#00ff7f;">Senden</button>
            <button id="cancel-mail-btn" style="background-color:#ff4d4d; float: right;">Abbrechen</button>
            <p id="send-status" style="margin-top: 10px; color: #f0e68c;"></p>
        </div>
    </div>

    <script>
        const allEmails = {
            inbox: <?php echo json_encode($inbox_emails); ?>,
            sent: <?php echo json_encode($sent_emails); ?>
        };

        document.addEventListener('DOMContentLoaded', function() {
            const listContainer = document.getElementById('email-list-container');
            const displaySubject = document.getElementById('display-subject');
            const displayLabel = document.getElementById('display-label');
            const displayPartner = document.getElementById('display-partner');
            const displayBody = document.getElementById('display-body');
            const inboxBtn = document.getElementById('inbox-btn');
            const sentBtn = document.getElementById('sent-btn');

            function renderEmailList(type) {
                listContainer.innerHTML = '';
                allEmails[type].forEach(email => {
                    const item = document.createElement('div');
                    item.className = 'email-item';
                    item.dataset.emailData = JSON.stringify(email);
                    let partnerLabel = (type === 'inbox') ? 'Von:' : 'An:';
                    let partnerName = (type === 'inbox') ? email.sender_name : (email.recipient_email || 'Unbekannt');
                    item.innerHTML = `<div><strong>${partnerLabel}</strong> ${partnerName}</div><div><strong>Betreff:</strong> ${email.subject}</div>`;
                    item.addEventListener('click', viewEmail);
                    listContainer.appendChild(item);
                });
            }

            function viewEmail() {
                document.querySelectorAll('.email-item').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                const data = JSON.parse(this.dataset.emailData);
                displaySubject.textContent = data.subject;
                displayLabel.textContent = (data.type === 'inbox') ? 'Von:' : 'An:';
                displayPartner.textContent = (data.type === 'inbox') ? data.sender_name : (data.recipient_email || 'Unbekannt');
                displayBody.innerHTML = data.body_html; // body_html ist jetzt direkt das ganze HTML
            }

            inboxBtn.addEventListener('click', () => {
                renderEmailList('inbox');
                inboxBtn.classList.add('active');
                sentBtn.classList.remove('active');
            });
            sentBtn.addEventListener('click', () => {
                renderEmailList('sent');
                sentBtn.classList.add('active');
                inboxBtn.classList.remove('active');
            });

            const composeModal = document.getElementById('compose-modal');
            document.getElementById('compose-btn').addEventListener('click', () => composeModal.classList.remove('hidden'));
            document.getElementById('cancel-mail-btn').addEventListener('click', () => composeModal.classList.add('hidden'));

            document.getElementById('send-mail-btn').addEventListener('click', async () => {
                const recipient = document.getElementById('recipient-email').value;
                const subject = document.getElementById('subject-email').value;
                const body = document.getElementById('body-email').value;
                const statusEl = document.getElementById('send-status');
                statusEl.textContent = 'Sende...';

                // DIESE LOGIK MUSS GGF. ANGEPASST WERDEN, WENN SPIELER SELBST E-MAILS SENDEN SOLLEN.
                // Die aktuelle Architektur konzentriert sich auf vom System/Level gesendete E-Mails.
                const formData = new FormData();
                formData.append('to', recipient);
                formData.append('subject', subject);
                formData.append('body', body);

                const response = await fetch('api_send_email.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                statusEl.textContent = result.message;

                if (result.success) {
                    setTimeout(() => {
                        composeModal.classList.add('hidden');
                        statusEl.textContent = '';
                        window.location.reload();
                    }, 1500);
                }
            });
            renderEmailList('inbox');
        });
    </script>

</body>

</html>