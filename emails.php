<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit("Bitte einloggen."); }
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Posteingang abrufen
$stmt_inbox = $pdo->prepare("SELECT *, 'inbox' as type FROM emails WHERE recipient_id = :user_id ORDER BY sent_at DESC");
$stmt_inbox->execute([':user_id' => $user_id]);
$inbox_emails = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

// Gesendete E-Mails abrufen
$stmt_sent = $pdo->prepare("SELECT e.*, u.email as recipient_email, 'sent' as type FROM emails e JOIN users u ON e.recipient_id = u.id WHERE e.sender_id = :user_id ORDER BY sent_at DESC");
$stmt_sent->execute([':user_id' => $user_id]);
$sent_emails = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Postfach</title>
    <style>
        html, body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; color: #e0e0e0; background-color: #1a1a1a; height: 100vh; overflow: hidden; }
        .mail-client { display: flex; flex-direction: column; height: 100%; }
        .toolbar { padding: 10px; background: #2a2a2a; border-bottom: 1px solid #333; display: flex; gap: 10px; }
        .toolbar button { padding: 8px 15px; background-color: #444; color: white; border: 1px solid #555; cursor: pointer; border-radius: 3px; }
        .toolbar button.active { background-color: #00ff7f; color: #0d0d0d; border-color: #00ff7f; }
        .main-area { display: flex; flex-grow: 1; overflow: hidden; }
        .email-list { width: 350px; border-right: 1px solid #333; overflow-y: auto; background-color: #111;}
        .email-item { padding: 15px 10px; border-bottom: 1px solid #2a2a2a; cursor: pointer; }
        .email-item:hover, .email-item.active { background-color: #2a2a2a; }
        .email-display { flex-grow: 1; padding: 20px; overflow-y: auto; }
        .email-body { white-space: pre-wrap; word-wrap: break-word; line-height: 1.6; color: #ccc; }
        .hidden { display: none !important; }
        #compose-modal { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 1001;}
        #compose-form { background: #2a2a2a; padding: 20px; border-radius: 5px; width: 90%; max-width: 600px; box-shadow: 0 5px 15px black; }
        #compose-form input, #compose-form textarea { width: 100%; padding: 8px; margin-bottom: 10px; background: #1a1a1a; border: 1px solid #333; color: white; box-sizing: border-box; }
        #compose-form button { padding: 10px 15px; border: none; cursor: pointer; border-radius: 3px; }
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
                let partnerName = (type === 'inbox') ? email.sender_name : email.recipient_email;
                item.innerHTML = `<div><strong>${partnerLabel}</strong> ${partnerName || 'Unbekannt'}</div><div><strong>Betreff:</strong> ${email.subject}</div>`;
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
            displayPartner.textContent = (data.type === 'inbox') ? data.sender_name : data.recipient_email;
            displayBody.innerHTML = data.body_html.replace(/\n/g, '<br>');
        }

        inboxBtn.addEventListener('click', () => { renderEmailList('inbox'); inboxBtn.classList.add('active'); sentBtn.classList.remove('active'); });
        sentBtn.addEventListener('click', () => { renderEmailList('sent'); sentBtn.classList.add('active'); inboxBtn.classList.remove('active'); });

        const composeModal = document.getElementById('compose-modal');
        document.getElementById('compose-btn').addEventListener('click', () => composeModal.classList.remove('hidden'));
        document.getElementById('cancel-mail-btn').addEventListener('click', () => composeModal.classList.add('hidden'));

        document.getElementById('send-mail-btn').addEventListener('click', async () => {
            const recipient = document.getElementById('recipient-email').value;
            const subject = document.getElementById('subject-email').value;
            const body = document.getElementById('body-email').value;
            const statusEl = document.getElementById('send-status');
            statusEl.textContent = 'Sende...';
            
            const formData = new FormData();
            formData.append('to', recipient);
            formData.append('subject', subject);
            formData.append('body', body);

            const response = await fetch('api_send_email.php', { method: 'POST', body: formData });
            const result = await response.json();
            statusEl.textContent = result.message;

            if (result.success) {
                setTimeout(() => {
                    composeModal.classList.add('hidden');
                    statusEl.textContent = '';
                    // E-Mail-Listen neu laden und anzeigen (optional, aber empfohlen)
                    window.location.reload(); 
                }, 1500);
            }
        });
        renderEmailList('inbox');
    });
</script>

</body>
</html>