<?php
session_start();
// Stellt sicher, dass nur eingeloggte Benutzer auf den Desktop zugreifen können.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Diese Datei ist jetzt nur noch die reine "Bühne" für die Anwendung.
// Die gesamte Spiellogik findet in den Iframe-Konsolen statt.
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
            <span class="badge" id="email-badge">0</span>
        </div>


        <div class="desktop-icon" id="icon-browser">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/internet.png" alt="browser" />
            <span>Browser</span>
        </div>

        <div id="console-window" class="window-container hidden">
            <div class="window-header">
                <div class="console-tabs">
                    <button class="tab-link active" data-tab="missions">BND:\MISSIONS</button>
                    <button class="tab-link" data-tab="trainee">BND:\TRAINEE</button>
                </div>
                <button class="win-btn" data-window-id="console-window">X</button>
            </div>
            <div class="window-content" style="padding:0; background-color: #131313;">
                <div class="console-body">
                    <iframe id="missions-console-iframe" class="console-instance active" src="mission_console.php"></iframe>
                    <iframe id="trainee-console-iframe" class="console-instance" src="trainee_console.php"></iframe>
                </div>
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
            <div class="window-content" style="padding:0;"><iframe id="emails-iframe" src="emails.php" style="width:100%; height:100%; border:none;"></iframe></div>
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
            <div class="window-content" style="padding:0;"><iframe id="browser-iframe" src="browser.php?page=start.html" style="width:100%; height:100%; border:none;"></iframe></div>
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
            // --- GLOBALE VARIABLEN UND KONSTANTEN ---
            let highestZ = 101;
            const emailBadge = document.getElementById('email-badge');
            const notificationWindow = document.getElementById('notification-window');
            const notificationTitle = document.getElementById('notification-title');
            const notificationBody = document.getElementById('notification-body');
            const notificationClose = document.getElementById('notification-close');
            const consoleWindow = document.getElementById('console-window');


            // In desktop.php, innerhalb des <script>-Blocks

            // NEUE FUNKTION: Öffnet den Browser und lädt eine URL
            window.openBrowserAndLoadUrl = function(url) {
                const browserWindow = document.getElementById('browser-window');
                const browserIframe = document.getElementById('browser-iframe');

                // 1. Mache das Browser-Fenster sichtbar und bringe es nach vorne
                browserWindow.classList.remove('hidden');
                bringToFront(browserWindow); // bringToFront ist eine deiner bestehenden Funktionen

                // 2. Sage dem Browser-Iframe, die neue URL zu laden
                // Wir greifen auf die 'loadUrl'-Funktion zu, die bereits in browser.php existiert
                if (browserIframe && browserIframe.contentWindow && typeof browserIframe.contentWindow.loadUrl === 'function') {
                    browserIframe.contentWindow.loadUrl(url);
                } else {
                    // Fallback, falls die Funktion noch nicht geladen ist
                    browserIframe.src = 'browser.php?page=' + url.split('=').pop();
                }
            }



            // --- STEUERZENTRALE FÜR IFRAME-KOMMUNIKATION & EVENTS ---

            // Aktualisiert die "Ungelesen"-Zahl am E-Mail-Icon
            function updateUnreadCount() {
                fetch('api_get_unread_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.count > 0) {
                            emailBadge.textContent = data.count;
                            emailBadge.classList.add('visible');
                        } else {
                            emailBadge.classList.remove('visible');
                        }
                    });
            }
            window.forceEmailUpdate = updateUnreadCount; // Global verfügbar machen

            // Zeigt eine Benachrichtigung mit 1 Sekunde Verzögerung an
            window.showNotification = function(title, message) {
                setTimeout(() => {
                    notificationTitle.textContent = title;
                    notificationBody.innerHTML = message;
                    notificationWindow.classList.remove('hidden');
                    setTimeout(() => notificationWindow.classList.add('visible'), 50);
                }, 1000);
            }

            // Lädt ein Iframe neu UND aktualisiert den Zähler
            window.reloadIframe = function(iframeId) {
                const iframe = document.getElementById(iframeId);
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.location.reload();
                    if (iframeId === 'emails-iframe') {
                        // Zähler-Update nach Reload erzwingen
                        setTimeout(updateUnreadCount, 500);
                    }
                }
            }

            // Führt eine Aktion (wie E-Mail senden) verzögert aus
            // KORRIGIERT: Führt eine LISTE von verzögerten Aktionen aus
            window.triggerDelayedActions = function(actions) {
                if (!actions || !Array.isArray(actions)) return;

                actions.forEach(task => {
                    const delayMs = parseInt(task.delay, 10) * 1000;
                    setTimeout(() => {
                        if (task.action === 'showNotification') {
                            window.showNotification(task.data.title, task.data.message);
                        }
                        if (task.action === 'reloadIframe') {
                            window.reloadIframe(task.data.iframeId);
                        }
                    }, delayMs);
                });
            }

            function makeResizable(element) {
                const resizers = element.querySelectorAll('.resizer');
                const minimum_size = 250;
                resizers.forEach(function(resizer) {
                    let original_width = 0,
                        original_height = 0,
                        original_x = 0,
                        original_y = 0;
                    resizer.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        original_width = parseFloat(getComputedStyle(element, null).getPropertyValue('width').replace('px', ''));
                        original_height = parseFloat(getComputedStyle(element, null).getPropertyValue('height').replace('px', ''));
                        original_x = e.pageX;
                        original_y = e.pageY;
                        window.addEventListener('mousemove', resize);
                        window.addEventListener('mouseup', stopResize);
                    });

                    function resize(e) {
                        if (resizer.classList.contains('bottom-right')) {
                            const width = original_width + (e.pageX - original_x);
                            const height = original_height + (e.pageY - original_y);
                            if (width > minimum_size) element.style.width = width + 'px';
                            if (height > minimum_size) element.style.height = height + 'px';
                        }
                    }

                    function stopResize() {
                        window.removeEventListener('mousemove', resize);
                    }
                });
            }



            function bringToFront(element) {
                highestZ++;
                element.style.zIndex = highestZ;
            }

            function makeDraggable(element) {
                const header = element.querySelector('.window-header');
                if (!header) return;
                let pos1 = 0,
                    pos2 = 0,
                    pos3 = 0,
                    pos4 = 0;
                header.onmousedown = dragMouseDown;

                function dragMouseDown(e) {
                    e.preventDefault();
                    bringToFront(element);
                    pos3 = e.clientX;
                    pos4 = e.clientY;
                    document.onmouseup = closeDragElement;
                    document.onmousemove = elementDrag;
                }

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



            function initializeWindow(iconId, windowId) {
                const icon = document.getElementById(iconId);
                const windowEl = document.getElementById(windowId);
                if (!icon || !windowEl) return;
                const closeBtn = windowEl.querySelector('.win-btn');
                icon.addEventListener('click', () => {
                    windowEl.classList.remove('hidden');
                    bringToFront(windowEl);
                });
                if (closeBtn) closeBtn.addEventListener('click', () => windowEl.classList.add('hidden'));
                makeDraggable(windowEl);
                makeResizable(windowEl);
                windowEl.addEventListener('mousedown', () => bringToFront(windowEl));
            }


            // --- INITIALISIERUNG & EVENT LISTENERS ---

            // Alle Fenster initialisieren
            initializeWindow('icon-console', 'console-window');
            initializeWindow('icon-emails', 'emails-window');
            initializeWindow('icon-browser', 'browser-window');

            // Konsolen-Tabs initialisieren
            consoleWindow.querySelectorAll('.tab-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    consoleWindow.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
                    consoleWindow.querySelectorAll('.console-instance').forEach(c => c.classList.remove('active'));
                    const tabName = this.dataset.tab;
                    this.classList.add('active');
                    document.getElementById(tabName + '-console-iframe').classList.add('active');
                });
            });

            // Benachrichtigung schließen
            notificationClose.addEventListener('click', () => {
                notificationWindow.classList.remove('visible');
                setTimeout(() => notificationWindow.classList.add('hidden'), 500);
            });

            // E-Mail-Zähler beim Start und periodisch aktualisieren
            updateUnreadCount();
            setInterval(updateUnreadCount, 10000);
        });
    </script>
</body>

</html>