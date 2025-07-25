<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    exit("Sitzung abgelaufen.");
}
$user_id = $_SESSION['user_id'];

// --- Hilfsfunktion zum Senden von E-Mails ---
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

// --- Initialisierung ---
if (!isset($_SESSION['mission_context'])) {
    $_SESSION['mission_context'] = ['history' => []];
    send_ingame_email(1, $user_id, $pdo);
    $_SESSION['mission_context']['history'][] = ['type' => 'output', 'content' => "Willkommens-E-Mail wurde an Ihr Postfach gesendet.\n'start training' eingeben, um zu beginnen."];
    $_SESSION['reload_iframe'] = 'emails-iframe';
}

// --- FORMULAR-VERARBEITUNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = trim($_POST['command'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM mission_progress WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    $current_mission_id = $progress ? $progress['mission_id'] : 1;
    $current_step = $progress ? $progress['current_step'] : 0;

    $level_file = 'game_logic/level' . $current_mission_id . '.php';
    if (file_exists($level_file)) {
        require_once $level_file;
        $response = handle_level_command($user_id, $command, $current_mission_id, $current_step, $pdo);
    }

    if (($response['success'] ?? false) && ($response['expected_command'] ?? '') === 'clear') {
        $_SESSION['mission_context']['history'] = [];
    } else {
        $_SESSION['mission_context']['history'][] = ['type' => 'input', 'content' => $command];
    }

    $output = $response['console_output'] ?? "Befehl nicht erkannt: '" . htmlspecialchars($command) . "'";
    $_SESSION['mission_context']['history'][] = ['type' => 'output', 'content' => $output];

    if ($response['success'] ?? false) {
        // Events auslösen
        if (!empty($response['notification_title'])) {
            $_SESSION['show_notification'] = ['title' => $response['notification_title'], 'message' => $response['notification_message']];
        }
        if (!empty($response['email_id_to_send'])) {
            send_ingame_email($response['email_id_to_send'], $user_id, $pdo);
            $_SESSION['reload_iframe'] = 'emails-iframe';
        }
        if (!empty($response['delayed_action'])) {
            $_SESSION['delayed_action'] = $response['delayed_action'];
        }

        // --- HIER IST DER FINALE, ROBUSTE FIX ---
        // Fortschritt in der DB aktualisieren
        if ($response['mission_completed'] ?? false) {
            // Mission als abgeschlossen markieren
            $stmt = $pdo->prepare("UPDATE mission_progress SET status = 'completed' WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);
        } elseif (isset($response['next_step_id'])) {
            $next_step = $response['next_step_id'];

            if ($progress) {
                // Wenn ein Fortschritt existiert, aktualisiere ihn (Normalfall)
                $stmt = $pdo->prepare("UPDATE mission_progress SET current_step = :step, status = 'active' WHERE user_id = :uid");
                $stmt->execute([':step' => $next_step, ':uid' => $user_id]);
            } else {
                // Wenn kein Fortschritt existiert (nur bei 'start training'), erstelle einen neuen Eintrag
                $stmt = $pdo->prepare("INSERT INTO mission_progress (user_id, mission_id, current_step, status) VALUES (:uid, :mid, :step, 'active')");
                $stmt->execute([':uid' => $user_id, ':mid' => $current_mission_id, ':step' => $next_step]);
            }
        }
        // --- ENDE DES FIXES ---
    }

    header("Location: mission_console.php");
    exit();
}

// Session-Variablen für JavaScript holen und danach löschen
$notification_to_show = $_SESSION['show_notification'] ?? null;
unset($_SESSION['show_notification']);
$iframe_to_reload = $_SESSION['reload_iframe'] ?? null;
unset($_SESSION['reload_iframe']);
$delayed_action = $_SESSION['delayed_action'] ?? null;
unset($_SESSION['delayed_action']);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Missions-Konsole</title>
    <style>
        body,
        html {
            font-family: Consolas, 'Cascadia Code', 'Ubuntu Mono', Menlo, Monaco, 'Courier New', monospace;
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
        // Dein JavaScript bleibt hier unverändert...
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

        <?php if ($notification_to_show): ?>
            window.parent.showNotification('<?php echo addslashes($notification_to_show['title']); ?>', '<?php echo addslashes($notification_to_show['message']); ?>');
        <?php endif; ?>

        <?php if ($iframe_to_reload): ?>
            window.parent.reloadIframe('<?php echo $iframe_to_reload; ?>');
        <?php endif; ?>

        <?php if ($delayed_action): ?>
            window.parent.triggerDelayedAction(
                <?php echo $delayed_action['delay']; ?>,
                '<?php echo $delayed_action['action']; ?>',
                <?php echo json_encode($delayed_action['data']); ?>
            );
        <?php endif; ?>
    </script>
</body>

</html>