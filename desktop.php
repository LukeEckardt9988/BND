<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php';
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
            consoleInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const command = consoleInput.value;
                    if (command.trim() === '') return;
                    consoleOutput.innerHTML += `<br><span style="color: #f0e68c;">> ${command}</span><br>`;
                    consoleInput.value = '';
                    if (command.toLowerCase() === 'clear') {
                        consoleOutput.innerHTML = '';
                        return;
                    }

                    fetch('api_console.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'command=' + encodeURIComponent(command)
                        })
                        .then(response => response.json())
                        .then(data => {
                            const formattedOutput = data.output.replace(/\n/g, '<br>');
                            consoleOutput.innerHTML += `<span style="color: #00ff7f;">${formattedOutput}</span>`;
                            consoleOutput.scrollTop = consoleOutput.scrollHeight;

                            fetch('api_check_command.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'command=' + encodeURIComponent(command)
                                })
                                .then(triggerResponse => triggerResponse.json())
                                .then(triggerData => {
                                    if (triggerData.status === 'show_notification') {
                                        showNotification(triggerData.data.title, triggerData.data.message);
                                    }
                                });
                        })
                        .catch(error => {
                            consoleOutput.innerHTML += '<br>FATAL: Verbindung zum Command-Server verloren.';
                            consoleOutput.scrollTop = consoleOutput.scrollHeight;
                        });
                }
            });
        });
    </script>
</body>

</html>