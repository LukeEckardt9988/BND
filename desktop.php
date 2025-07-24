<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Die Initialisierung der Konsole findet jetzt vollständig in console.php statt.
// Diese Datei ist nur noch für die Darstellung des Desktops zuständig.
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

            // Füge dies innerhalb der DOMContentLoaded-Funktion hinzu

            // Logik für die Konsolen-Tabs
            const consoleWindow = document.getElementById('console-window');
            consoleWindow.querySelectorAll('.tab-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Alle Tabs und Inhalte deaktivieren
                    consoleWindow.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
                    consoleWindow.querySelectorAll('.console-instance').forEach(c => c.classList.remove('active'));

                    // Den geklickten Tab und den zugehörigen Inhalt aktivieren
                    const tabName = this.dataset.tab;
                    this.classList.add('active');
                    document.getElementById(tabName + '-console-iframe').classList.add('active');
                });
            });

            
            let highestZ = 101;

            // Funktion, um ein Fenster in den Vordergrund zu bringen
            function bringToFront(element) {
                highestZ++;
                element.style.zIndex = highestZ;
            }

            // Funktion, um Fenster verschiebbar zu machen
            function makeDraggable(element) {
                const header = element.querySelector('.window-header');
                let pos1 = 0,
                    pos2 = 0,
                    pos3 = 0,
                    pos4 = 0;
                if (!header) return;
                header.onmousedown = dragMouseDown;

                function dragMouseDown(e) {
                    e = e || window.event;
                    e.preventDefault();
                    bringToFront(element);
                    pos3 = e.clientX;
                    pos4 = e.clientY;
                    document.onmouseup = closeDragElement;
                    document.onmousemove = elementDrag;
                }

                function elementDrag(e) {
                    e = e || window.event;
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

            // Die Funktion für die Größenänderung bleibt hier, da sie für alle Fenster gilt.
            // (Code für makeResizable hier einfügen, falls benötigt)
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

            // Initialisiert die Fenster (Öffnen, Schließen, Dragging etc.)
            function initializeWindow(iconId, windowId) {
                const icon = document.getElementById(iconId);
                const windowEl = document.getElementById(windowId);
                if (!icon || !windowEl) return;

                const closeBtn = windowEl.querySelector('.win-btn');

                icon.addEventListener('click', () => {
                    windowEl.classList.remove('hidden');
                    bringToFront(windowEl);
                });

                if (closeBtn) {
                    closeBtn.addEventListener('click', () => windowEl.classList.add('hidden'));
                }

                makeDraggable(windowEl);
                makeResizable(windowEl); // Hier bei Bedarf aktivieren
                windowEl.addEventListener('mousedown', () => bringToFront(windowEl));
            }

            initializeWindow('icon-console', 'console-window');
            initializeWindow('icon-emails', 'emails-window');
            initializeWindow('icon-browser', 'browser-window');

            // --- STEUERZENTRALE FÜR IFRAME-KOMMUNIKATION ---
            const notificationWindow = document.getElementById('notification-window');
            const notificationTitle = document.getElementById('notification-title');
            const notificationBody = document.getElementById('notification-body');
            const notificationClose = document.getElementById('notification-close');

            // Globale Funktion, die das Iframe (console.php) aufrufen kann
            window.showNotification = function(title, message) {
                notificationTitle.textContent = title;
                notificationBody.innerHTML = message;
                notificationWindow.classList.remove('hidden');
                setTimeout(() => {
                    notificationWindow.classList.add('visible');
                }, 50);
            }

            // Globale Funktion zum Neuladen anderer Iframes
            window.reloadIframe = function(iframeId) {
                const iframe = document.getElementById(iframeId);
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.location.reload();
                }
            }

            notificationClose.addEventListener('click', () => {
                notificationWindow.classList.remove('visible');
                setTimeout(() => {
                    notificationWindow.classList.add('hidden');
                }, 500);
            });
        });
    </script>
</body>

</html>