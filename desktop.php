<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php'; // Stellen Sie sicher, dass db_connect.php korrekt eingebunden ist

$user_id = $_SESSION['user_id'];
$initial_console_message = '';

try {
    // Prüfen, ob der Benutzer eine aktive Mission hat
    $stmt = $pdo->prepare("SELECT mission_id, current_step FROM mission_progress WHERE user_id = :user_id AND status = 'active'");
    $stmt->execute([':user_id' => $user_id]);
    $active_mission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$active_mission) {
        // Benutzer hat keine aktive Mission, also ist es ein neuer Start oder Mission abgeschlossen.
        // E-Mail 1 (erste Mission) an den Benutzer senden, falls noch nicht geschehen
        $stmt_check_email = $pdo->prepare("SELECT id FROM emails WHERE id = 1 AND recipient_id = :user_id");
        $stmt_check_email->execute([':user_id' => $user_id]);
        $email_sent = $stmt_check_email->fetch(PDO::FETCH_ASSOC);

        if (!$email_sent) {
            // E-Mail senden (recipient_id setzen)
            // Dies ist der "Startschuss" für den Spieler, um die Mission zu initiieren
            $stmt_send_email = $pdo->prepare("UPDATE emails SET recipient_id = :user_id, is_read = 0, sent_at = NOW() WHERE id = 1");
            $stmt_send_email->execute([':user_id' => $user_id]);
            $initial_console_message = "Willkommen, Rekrut! Eine neue E-Mail wartet in Ihrem Posteingang. Überprüfen Sie diese für Ihre erste Mission.<br>Geben Sie '<b>start training</b>' ein, um Ihre Ausbildung zu beginnen.";
        } else {
            // E-Mail wurde bereits gesendet, aber Mission noch nicht gestartet
            $initial_console_message = "Willkommen zurück, Rekrut! Ihre erste Mission wartet. Überprüfen Sie Ihre E-Mails oder geben Sie '<b>start training</b>' ein, um zu beginnen.";
        }
    } else {
        // Benutzer hat eine aktive Mission, normale Anzeige
        $initial_console_message = "Willkommen zurück, Rekrut! Ihre aktuelle Mission: Level " . $active_mission['mission_id'] . ", Schritt " . $active_mission['current_step'] . ".";
        // Hier keine Beschreibung aus mission_steps laden, da Briefing per E-Mail kommt.
        // Optional könnte man hier eine kurze Erinnerung an die Aufgabe einfügen, falls gewünscht.
        // Beispiel:
        $stmt_step_desc = $pdo->prepare("SELECT description FROM mission_steps WHERE mission_id = :mid AND step_number = :snum");
        $stmt_step_desc->execute([':mid' => $active_mission['mission_id'], ':snum' => $active_mission['current_step']]);
        $step_description = $stmt_step_desc->fetchColumn();
        if ($step_description) {
            $initial_console_message .= "<br>Aktuelle Aufgabe: " . htmlspecialchars($step_description);
        }
    }
} catch (PDOException $e) {
    error_log("Database error in desktop.php: " . $e->getMessage());
    $initial_console_message = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>BND Desktop</title>
    <link rel="stylesheet" href="desktop.css">
</head>

<body>
    <div id="desktop">
        <div class="desktop-icon" id="icon-console">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/console.png" alt="console" />
            <span>Konsole</span>
        </div>
        <div class="desktop-icon" id="icon-emails">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/new-post.png" alt="emails" />
            <span>E-Mails</span>
        </div>
        <div class="desktop-icon" id="icon-browser">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/internet.png" alt="browser" />
            <span>Browser</span>
        </div>


        <div id="console-window" class="window-container hidden">
            <div class="window-header"><span>BND Secure Terminal</span><button class="win-btn" data-window-id="console-window">X</button></div>
            <div class="window-content">
                <div id="console-output" class="console-output-area"></div>
                <div class="console-input-line"><span class="prompt">></span><input type="text" id="console-input" class="console-input"></div>
            </div>
            <div class="resizer top-left"></div>
            <div class="resizer top-right"></div>
            <div class="resizer bottom-left"></div>
            <div class="resizer bottom-right"></div>
            <div class="resizer top"></div>
            <div class="resizer bottom"></div>
            <div class="resizer left"></div>
            <div class="resizer right"></div>
        </div>

        <div id="emails-window" class="window-container hidden">
            <div class="window-header"><span>Posteingang</span><button class="win-btn" data-window-id="emails-window">X</button></div>
            <div class="window-content" style="padding:0;"><iframe src="emails.php" style="width:100%; height:100%; border:none;"></iframe></div>
            <div class="resizer top-left"></div>
            <div class="resizer top-right"></div>
            <div class="resizer bottom-left"></div>
            <div class="resizer bottom-right"></div>
            <div class="resizer top"></div>
            <div class="resizer bottom"></div>
            <div class="resizer left"></div>
            <div class="resizer right"></div>
        </div>

        <div id="browser-window" class="window-container hidden">
            <div class="window-header"><span>BND Intranet Browser</span><button class="win-btn" data-window-id="browser-window">X</button></div>
            <div class="window-content" style="padding:0;"><iframe src="browser.php?page=start.html" style="width:100%; height:100%; border:none;"></iframe></div>
            <div class="resizer top-left"></div>
            <div class="resizer top-right"></div>
            <div class="resizer bottom-left"></div>
            <div class="resizer bottom-right"></div>
            <div class="resizer top"></div>
            <div class="resizer bottom"></div>
            <div class="resizer left"></div>
            <div class="resizer right"></div>
        </div>
    </div>

    <div id="notification-window" class="hidden">
        <div id="notification-header">
            <span id="notification-title">System-Meldung</span>
            <span id="notification-close" style="cursor: pointer;">X</span>
        </div>
        <div id="notification-body"></div>
    </div>

    <div id="taskbar">
        <a href="logout.php" class="logout-link">Ausloggen</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let highestZ = 101;

            function bringToFront(element) {
                highestZ++;
                element.style.zIndex = highestZ;
            }

            function makeDraggable(element) {
                const header = element.querySelector('.window-header');
                let pos1 = 0,
                    pos2 = 0,
                    pos3 = 0,
                    pos4 = 0;
                if (!header) return;
                header.onmousedown = function(e) {
                    e.preventDefault();
                    bringToFront(element);
                    pos3 = e.clientX;
                    pos4 = e.clientY;
                    document.onmouseup = closeDragElement;
                    document.onmousemove = elementDrag;
                };

                function elementDrag(e) {
                    e.preventDefault();
                    pos1 = pos3 - e.clientX;
                    pos2 = pos4 - e.clientY;
                    pos3 = e.clientX;
                    pos4 = e.clientY;
                    element.style.top = (element.offsetTop - pos2) + "px";
                    element.style.left = (element.offsetLeft - pos1) + "px";
                }

                function closeDragElement() {
                    document.onmouseup = null;
                    document.onmousemove = null;
                }
            }

            function makeResizable(element) {
                const resizers = element.querySelectorAll('.resizer');
                const minimum_size = 200;
                let original_width = 0,
                    original_height = 0,
                    original_mouse_x = 0,
                    original_mouse_y = 0;
                resizers.forEach(function(resizer) {
                    resizer.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        original_width = parseFloat(getComputedStyle(element, null).getPropertyValue('width').replace('px', ''));
                        original_height = parseFloat(getComputedStyle(element, null).getPropertyValue('height').replace('px', ''));
                        original_mouse_x = e.pageX;
                        original_mouse_y = e.pageY;
                        window.addEventListener('mousemove', resize);
                        window.addEventListener('mouseup', stopResize);
                    });

                    function resize(e) {
                        if (resizer.classList.contains('bottom-right')) {
                            const width = original_width + (e.pageX - original_mouse_x);
                            const height = original_height + (e.pageY - original_mouse_y);
                            if (width > minimum_size) {
                                element.style.width = width + 'px';
                            }
                            if (height > minimum_size) {
                                element.style.height = height + 'px';
                            }
                        }
                    }

                    function stopResize() {
                        window.removeEventListener('mousemove', resize);
                    }
                });
            }

            function initializeWindow(iconId, windowId) {
                const icon = document.getElementById(iconId);
                const windowEl = document.getElementById(windowId);
                const closeBtn = windowEl.querySelector('.win-btn');
                if (icon && windowEl && closeBtn) {
                    icon.addEventListener('click', () => {
                        windowEl.classList.remove('hidden');
                        bringToFront(windowEl);
                    });
                    closeBtn.addEventListener('click', () => windowEl.classList.add('hidden'));
                    makeDraggable(windowEl);
                    makeResizable(windowEl);
                    windowEl.addEventListener('mousedown', () => bringToFront(windowEl));
                }
            }

            initializeWindow('icon-console', 'console-window');
            initializeWindow('icon-emails', 'emails-window');
            initializeWindow('icon-browser', 'browser-window');

            const notificationWindow = document.getElementById('notification-window');
            const notificationTitle = document.getElementById('notification-title');
            const notificationBody = document.getElementById('notification-body');
            const notificationClose = document.getElementById('notification-close');

            function showNotification(title, message) {
                notificationTitle.textContent = title;
                notificationBody.innerHTML = message;
                notificationWindow.classList.remove('hidden');
                setTimeout(() => {
                    notificationWindow.classList.add('visible');
                }, 50);
            }

            notificationClose.addEventListener('click', () => {
                notificationWindow.classList.remove('visible');
                setTimeout(() => {
                    notificationWindow.classList.add('hidden');
                }, 500);
            });

            const consoleInput = document.getElementById('console-input');
            const consoleOutput = document.getElementById('console-output');
            let isTyping = false; // Flag to prevent multiple command executions

            // Initial console message on desktop load
            const initialMessage = `<?php echo addslashes($initial_console_message); ?>`; // PHP-Variable in JS übergeben
            if (initialMessage) {
                setTimeout(() => {
                    typewriterEffect(initialMessage, consoleOutput, () => {
                        consoleInput.disabled = false;
                        consoleInput.focus();
                    });
                }, 500); // Kleine Verzögerung für den Effekt
            } else {
                consoleInput.disabled = false; // Input sofort aktivieren, wenn keine Init-Nachricht
                consoleInput.focus();
            }


            // Function for typewriter effect
            function typewriterEffect(text, targetElement, onComplete) {
                isTyping = true;
                if (targetElement.innerHTML.trim() !== '') {
                    targetElement.innerHTML += '<br>';
                }

                const lines = text.split('<br>');
                let lineIndex = 0;
                // Die gewünschte helle Farbe für die Ausgabe
                const outputColor = '#f0e68c';

                const interval = setInterval(() => {
                    if (lineIndex < lines.length) {
                        // Wickeln Sie die Ausgabelinie in ein Span-Tag mit der gewünschten Farbe ein
                        targetElement.innerHTML += `<span style="color: ${outputColor};">${lines[lineIndex]}</span><br>`;
                        targetElement.scrollTop = targetElement.scrollHeight;
                        lineIndex++;
                    } else {
                        clearInterval(interval);
                        isTyping = false;
                        if (onComplete) onComplete();
                    }
                }, 50);
            }


            consoleInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !isTyping) {
                    const command = consoleInput.value.trim();
                    if (command === '') return;

                    consoleOutput.innerHTML += `<br><span style="color: #f0e68c;">> ${command}</span><br>`;
                    consoleInput.value = '';
                    consoleInput.disabled = true; // Disable input while processing

                    if (command.toLowerCase() === 'clear') {
                        consoleOutput.innerHTML = '';
                        consoleInput.disabled = false;
                        return;
                    }

                    // Send command to the new central API
                    fetch('api_game_command.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'command=' + encodeURIComponent(command)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.console_output) {
                                typewriterEffect(data.console_output.replace(/\n/g, '<br>'), consoleOutput, () => {
                                    consoleInput.disabled = false; // Re-enable input after typing
                                    consoleInput.focus();
                                    // Handle notifications if any
                                    if (data.notification) {
                                        showNotification(data.notification.title, data.notification.message);
                                    }
                                    // Handle unlocked programs (e.g., add new icons or update toolbox)
                                    if (data.unlocked_program) {
                                        showNotification('Neues Programm freigeschaltet!', `${data.unlocked_program.name}: ${data.unlocked_program.description}`);
                                    }
                                    // If an email was sent, reload emails frame
                                    if (data.email_sent) {
                                        const emailsIframe = document.querySelector('#emails-window iframe');
                                        if (emailsIframe && emailsIframe.contentWindow) {
                                            emailsIframe.contentWindow.location.reload();
                                        }
                                    }
                                    // If a mission step was completed or status changed,
                                    // and the browser window is showing a mission page, reload it.
                                    // This requires the browser iframe to be opened on levelX.php
                                    // In this new setup, we don't have mission_briefing.php, but if you introduce
                                    // other dynamic pages, this could be useful.
                                    if (data.reload_iframe_id) {
                                        const targetIframe = document.querySelector(`#${data.reload_iframe_id} iframe`);
                                        if (targetIframe && targetIframe.contentWindow) {
                                            targetIframe.contentWindow.location.reload();
                                        }
                                    }
                                });
                            } else {
                                consoleInput.disabled = false; // Re-enable input even if no output
                                consoleInput.focus();
                            }
                        })
                        .catch(error => {
                            typewriterEffect('FATAL: Verbindung zum Game-Server verloren.', consoleOutput, () => {
                                consoleInput.disabled = false;
                                consoleInput.focus();
                            });
                            console.error('API call failed:', error);
                        });
                }
            });
        });
    </script>
</body>

</html>