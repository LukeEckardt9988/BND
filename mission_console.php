<?php
session_start();
require_once 'db_connect.php';

// --- Grundlegende Initialisierung ---
if (!isset($_SESSION['user_id'])) {
    exit("Sitzung abgelaufen. Bitte das Fenster schließen und neu einloggen.");
}
$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['mission_context'])) {
    $_SESSION['mission_context'] = [
        'history' => [['type' => 'output', 'content' => "Missions-Terminal bereit. 'start training' eingeben, um zu beginnen."]]
    ];
}

// --- HILFSFUNKTION ZUM SENDEN VON E-MAILS ---
if (!function_exists('send_ingame_email')) {
    function send_ingame_email($template_id, $user_id, $pdo)
    {
        $stmt_get_template = $pdo->prepare("SELECT * FROM email_templates WHERE id = :template_id");
        $stmt_get_template->execute([':template_id' => $template_id]);
        $template_data = $stmt_get_template->fetch(PDO::FETCH_ASSOC);

        if ($template_data) {
            $stmt_insert_email = $pdo->prepare(
                "INSERT INTO emails (template_id, recipient_id, sent_at, is_read, is_phishing_copy, phishing_analysis_data_copy)
                 VALUES (:template_id, :user_id, NOW(), 0, :is_phishing, :phishing_data)"
            );
            $stmt_insert_email->execute([
                ':template_id' => $template_data['id'],
                ':user_id' => $user_id,
                ':is_phishing' => $template_data['is_phishing'],
                ':phishing_data' => $template_data['phishing_analysis_data']
            ]);
            return true;
        }
        return false;
    }
}


// --- FORMULAR-VERARBEITUNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = trim($_POST['command'] ?? '');
    $_SESSION['mission_context']['history'][] = ['type' => 'input', 'content' => $command];

    $output = "Befehl nicht erkannt: '" . htmlspecialchars($command) . "'";

    // --- SPIELLOGIK ---
    $stmt = $pdo->prepare("SELECT mission_id, current_step, status FROM mission_progress WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    if (strtolower($command) === 'start training') {
        if (!$progress || $progress['status'] !== 'active') {
            $mission_id_to_start = 1;
            $first_step_number = 1;

            $stmt_upsert = $pdo->prepare(
                "INSERT INTO mission_progress (user_id, mission_id, current_step, status) VALUES (:user_id, :mission_id, :step, 'active')
                 ON DUPLICATE KEY UPDATE mission_id = VALUES(mission_id), current_step = VALUES(current_step), status = 'active'"
            );
            $stmt_upsert->execute([':user_id' => $user_id, ':mission_id' => $mission_id_to_start, ':step' => $first_step_number]);

            require_once 'game_logic/level1.php';
            $step_def = get_level_step_definition($first_step_number);
            $output = "Level 1 gestartet. Ihre erste Anweisung wurde Ihnen per E-Mail zugesendet.\n\n" .
                "Aktuelle Aufgabe: " . ($step_def['description_for_display'] ?? "Unbekannt");

            // *** HIER WIRD JETZT DIE E-MAIL GESENDET ***
            send_ingame_email(1, $user_id, $pdo); // E-Mail mit Template-ID 1 senden

            $_SESSION['reload_iframe'] = 'emails-iframe';
            $_SESSION['show_notification'] = ['title' => 'Mission Gestartet', 'message' => 'Sie haben eine neue E-Mail erhalten.'];
        } else {
            $output = "Training läuft bereits. Sie sind in Mission " . $progress['mission_id'] . ".";
        }
    } elseif ($progress && $progress['status'] === 'active') {
        $level_file = 'game_logic/level' . $progress['mission_id'] . '.php';
        if (file_exists($level_file)) {
            require_once $level_file;
            $response = handle_level_command($user_id, $command, $progress['mission_id'], $progress['current_step'], $pdo);
            $output = $response['console_output'];

            if ($response['success']) {
                if (!empty($response['notification_title'])) {
                    $_SESSION['show_notification'] = ['title' => $response['notification_title'], 'message' => $response['notification_message']];
                }
                if (!empty($response['email_id_to_send'])) {
                    // *** HIER WIRD JETZT DIE E-MAIL GESENDET ***
                    send_ingame_email($response['email_id_to_send'], $user_id, $pdo);
                    $_SESSION['reload_iframe'] = 'emails-iframe';
                }

                if ($response['mission_completed']) {
                    $stmt_update = $pdo->prepare("UPDATE mission_progress SET status = 'completed' WHERE user_id = :user_id");
                    $stmt_update->execute([':user_id' => $user_id]);
                } else {
                    $stmt_update = $pdo->prepare("UPDATE mission_progress SET current_step = :next_step WHERE user_id = :user_id");
                    $stmt_update->execute([':next_step' => $response['next_step_id'], ':user_id' => $user_id]);
                }
            }
        } else {
            $output = "SYSTEMFEHLER: Logik-Datei für Mission " . $progress['mission_id'] . " nicht gefunden.";
        }
    }

    $_SESSION['mission_context']['history'][] = ['type' => 'output', 'content' => $output];
    header("Location: mission_console.php");
    exit();
}

$notification_to_show = $_SESSION['show_notification'] ?? null;
unset($_SESSION['show_notification']);
$iframe_to_reload = $_SESSION['reload_iframe'] ?? null;
unset($_SESSION['reload_iframe']);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Missions-Konsole</title>
    <style>
        body,
        html {
            font-family: 'Courier New', monospace;
            background-color: #131313;
            color: #e0e0e0;
            padding: 10px;
        }

        p {
            margin: 0 0 1em 0;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .input-line {
            display: flex;
        }

        .prompt {
            color: #f0e68c;
            margin-right: 5px;
        }

        .input-history {
            color: #00ff7f;
        }

        #live-input {
            color: #00ff7f;
            outline: none;
        }

        #cursor {
            background-color: #00ff7f;
            display: inline-block;
            width: 8px;
            animation: blink 1s step-end infinite;
        }

        @keyframes blink {

            from,
            to {
                background-color: transparent;
            }

            50% {
                background-color: #00ff7f;
            }
        }
    </style>
</head>

<body>
    <div id="history">
        <?php foreach ($_SESSION['mission_context']['history'] as $line): ?>
            <?php if ($line['type'] === 'input'): ?>
                <div class="input-line"><span class="prompt">BND:\MISSIONS></span>
                    <p class="input-history"><?php echo htmlspecialchars($line['content']); ?></p>
                </div>
            <?php else: ?>
                <p><?php echo $line['content']; ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="input-line">
        <span class="prompt">BND:\MISSIONS></span>
        <span id="live-input" contenteditable="false"></span>
        <span id="cursor">&nbsp;</span>
    </div>

    <form id="command-form" method="POST" action="mission_console.php" style="display:none;">
        <input type="hidden" name="command" id="command-input">
    </form>

    <script>
        // Das JavaScript bleibt exakt wie in der vorherigen Version
        const liveInput = document.getElementById('live-input');
        const form = document.getElementById('command-form');
        const hiddenInput = document.getElementById('command-input');

        document.body.focus();
        window.onclick = () => document.body.focus();

        document.addEventListener('keydown', function(e) {
            if (!e.metaKey && !e.ctrlKey) e.preventDefault();
            if (e.key === 'Enter') {
                hiddenInput.value = liveInput.textContent;
                form.submit();
            } else if (e.key === 'Backspace') {
                liveInput.textContent = liveInput.textContent.slice(0, -1);
            } else if (e.key.length === 1) {
                liveInput.textContent += e.key;
            }
            window.scrollTo(0, document.body.scrollHeight);
        });

        window.scrollTo(0, document.body.scrollHeight);

        <?php if ($notification_to_show || $iframe_to_reload): ?>
            if (window.parent) {
                <?php if ($notification_to_show): ?>
                    window.parent.showNotification('<?php echo addslashes($notification_to_show['title']); ?>', '<?php echo addslashes($notification_to_show['message']); ?>');
                <?php endif; ?>
                <?php if ($iframe_to_reload): ?>
                    window.parent.reloadIframe('<?php echo $iframe_to_reload; ?>');
                <?php endif; ?>
            }
        <?php endif; ?>
    </script>
</body>

</html>