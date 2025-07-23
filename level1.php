<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php';

$sql = "SELECT id, name, description FROM programs WHERE is_initial = TRUE";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Mission 1</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="level1.css">
</head>

<body>
    <div class="container">
        <div class="panel instructions">
            <div>
                <h2>Missions-Briefing</h2>
                <p><strong>Aufgabe:</strong> Überprüfe das interne Test-Netzwerk auf unautorisierte Aktivitäten.</p>
                <p><strong>Ziel-Netzwerk:</strong> 10.0.10.0/24</p>
                <hr>
                <h3 class="info-header">Toolbox 🛠️</h3>
                <div id="toolbox-container" class="toolbox">
                    <?php foreach ($programs as $program): ?>
                        <button class="program-btn" data-id="<?php echo $program['id']; ?>" title="<?php echo htmlspecialchars($program['description']); ?>">
                            <?php echo htmlspecialchars($program['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <h3 class="info-header">Befehls-Info</h3>
                <div id="command-explanation">Wähle ein Programm und einen Befehl aus, um eine Erklärung zu sehen.</div>
            </div>
            <div class="info-block">
                <h3 class="info-header">System-Meldungen</h3>
                <div id="hint-display">Hier werden wichtige Hinweise und Alarme angezeigt.</div>
            </div>
        </div>
        <div class="console-parent-container">
            <div class="panel console-container">
                <div id="console-output">CIA Secure Terminal v2.1<br>Bereit...<br></div>
            </div>
            <div class="console-input-area">
                <select id="command-dropdown" disabled>
                    <option>-- Programm auswählen --</option>
                </select>
                <input type="text" id="argument-input" placeholder="---" disabled>
                <button id="execute-btn" disabled>Ausführen</button>
            </div>
        </div>
    </div>

    <div id="modal-overlay" class="hidden">
        <div id="modal-content">
            <img src="image/nmap.jpg" alt="Nmap Logo">
            <h2>Debriefing: Werkzeug-Analyse</h2>

            <div id="nmap-page-1" class="modal-page active">
                <p>
                    <strong>Nmap (Network Mapper)</strong> ist ein vielseitiges Open-Source-Tool zur Netzwerkerkundung und Sicherheitsanalyse. Stell es dir vor wie einen Scanner, der dir zeigt, welche "Türen" (Ports) auf den Geräten in deinem Netzwerk offen sind und welche Dienste dahinterstecken.
                </p>
            </div>

            <div id="nmap-page-2" class="modal-page hidden">
                <h3>Was Nmap kann:</h3>
                <ul>
                    <li><strong>Geräte finden:</strong> Zeigt dir, welche Computer, Server oder IoT-Geräte online sind und welche IP-Adressen sie haben.</li>
                    <li><strong>Offene Türen entdecken:</strong> Findet offene Ports und verrät dir, welche Dienste (z.B. Webserver, Mailserver) darauf laufen – oft sogar mit der genauen Softwareversion. Das ist wichtig, um Sicherheitslücken zu erkennen.</li>
                    <li><strong>Betriebssysteme erraten:</strong> Versucht zu bestimmen, welches Betriebssystem auf einem Gerät läuft (z.B. Windows, Linux).</li>
                    <li><strong>Sicherheitschecks:</strong> Kann mit speziellen Skripten (NSE) nach bekannten Schwachstellen suchen, Konfigurationsfehler aufspüren und detaillierte Infos zu Diensten liefern.</li>
                    <li><strong>Netzwerk-Einblicke:</strong> Zeigt dir die Struktur deines Netzwerks und kann Firewalls erkennen.</li>
                </ul>
                <p>Du hast gelernt, Nmap für verschiedene Scans zu nutzen:</p>
                <ul>
                    <li><strong>-sn:</strong> Schnelle Entdeckung von aktiven Geräten.</li>
                    <li><strong>-sV:</strong> Detaillierte Analyse von Diensten und Versionen.</li>
                    <li><strong>-A:</strong> Aggressive, allumfassende Untersuchung eines Ziels.</li>
                </ul>
            </div>

            <div id="nmap-page-3" class="modal-page hidden">
                <h3>Goodie: iPhones finden mit Nmap</h3>
                <p>
                    iPhones (und andere iOS-Geräte) sind dafür bekannt, dass sie sich Nmap-Scans gegenüber oft "unfreundlich" verhalten. Sie schließen die meisten Ports, wenn sie sich im Ruhezustand befinden, um Energie zu sparen und die Sicherheit zu erhöhen.
                </p>
                <p>
                    Um ein iPhone mit Nmap zu finden, ist es oft am effektivsten, nach einem bestimmten Port zu suchen, der auch im Ruhezustand aktiv sein könnte. Der Port 62078/tcp ist ein solcher Kandidat, da er für den iTunes Wi-Fi Sync Service verwendet wird. Wenn der Benutzer diese Funktion aktiviert hat, könnte das iPhone auf diesem Port antworten.
                </p>
                <p>
                    Du könntest es so versuchen (ersetze [IP-Bereich] mit deinem lokalen Netzwerkbereich, z.B. 192.168.1.0/24):
                </p>
                <pre><code class="bash">sudo nmap -p 62078 --open [IP-Bereich]</code></pre>
                <p>
                    **Wichtig:** Verwende Nmap immer verantwortungsvoll und scanne nur Netzwerke, für die du die ausdrückliche Erlaubnis hast. Unautorisierte Scans sind illegal.
                </p>
            </div>

            <div class="modal-navigation">
                <button id="modal-prev-btn" class="nav-arrow hidden">&laquo; Zurück</button>
                <span id="modal-page-indicator">1 / 3</span>
                <button id="modal-next-btn" class="nav-arrow">Weiter &raquo;</button>
            </div>

            <div class="modal-buttons">
                <button id="modal-continue-btn" class="hidden">Nächste Mission</button>
            </div>
        </div>
    </div>

    <a id="next-level-arrow" class="glowing-arrow hidden">Mission abgeschlossen, klicke für Debriefing &raquo;</a>

    <script>
        // Haupt-Elemente
        const toolbox = document.getElementById('toolbox-container');
        const dropdown = document.getElementById('command-dropdown');
        const argumentInput = document.getElementById('argument-input');
        const executeBtn = document.getElementById('execute-btn');
        const consoleOutput = document.getElementById('console-output');
        const consoleContainer = document.querySelector('.console-container');
        const explanationDiv = document.getElementById('command-explanation');
        const hintDisplay = document.getElementById('hint-display');

        // Modal-Elemente
        const modalOverlay = document.getElementById('modal-overlay');
        const continueBtn = document.getElementById('modal-continue-btn'); // Nächste Mission Button
        const nextLevelArrow = document.getElementById('next-level-arrow'); // Pfeil zum Öffnen des Modals
        const modalPages = document.querySelectorAll('.modal-page'); // Alle Seiten innerhalb des Modals (Klasse)
        const modalPrevBtn = document.getElementById('modal-prev-btn'); // Zurück-Pfeil (ID)
        const modalNextBtn = document.getElementById('modal-next-btn'); // Weiter-Pfeil (ID)
        const modalPageIndicator = document.getElementById('modal-page-indicator'); // Seiten-Indikator (ID)

        let commandsForProgram = [];
        let isTyping = false;
        let currentPage = 0; // Aktuelle Seite des Modals, startet bei 0 (erste Seite)

        // Funktion zum Anzeigen der aktuellen Seite im Modal
        function showCurrentModalPage() {
            modalPages.forEach((page, index) => {
                if (index === currentPage) {
                    page.classList.remove('hidden');
                } else {
                    page.classList.add('hidden');
                }
            });

            // Pfeile und "Nächste Mission" Button steuern
            modalPrevBtn.classList.toggle('hidden', currentPage === 0); // Versteckt "Zurück" auf der ersten Seite
            modalNextBtn.classList.toggle('hidden', currentPage === modalPages.length - 1); // Versteckt "Weiter" auf der letzten Seite
            continueBtn.classList.toggle('hidden', currentPage !== modalPages.length - 1); // Zeigt "Nächste Mission" nur auf der letzten Seite

            // Seitenindikator aktualisieren
            modalPageIndicator.textContent = `${currentPage + 1} / ${modalPages.length}`;
        }

        // Event Listener für die Toolbox-Buttons
        toolbox.addEventListener('click', function(e) {
            if (e.target.classList.contains('program-btn')) {
                document.querySelectorAll('.program-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                let activeProgramId = e.target.dataset.id;
                dropdown.innerHTML = '<option>Lade...</option>';
                executeBtn.disabled = false;
                dropdown.disabled = false;

                fetch(`api_get_commands.php?program_id=${activeProgramId}`)
                    .then(response => response.json())
                    .then(data => {
                        commandsForProgram = data;
                        dropdown.innerHTML = '<option value="">-- Befehl auswählen --</option>';
                        commandsForProgram.forEach(cmd => {
                            const option = new Option(cmd.description, cmd.id);
                            dropdown.add(option);
                        });
                        explanationDiv.textContent = 'Wähle einen Befehl aus der Liste.';
                    });
            }
        });

        // Event Listener für das Befehls-Dropdown
        dropdown.addEventListener('change', function() {
            const commandId = this.value;
            if (commandId === "") {
                argumentInput.disabled = true;
                argumentInput.placeholder = '---';
                explanationDiv.textContent = 'Wähle einen Befehl aus der Liste.';
                return;
            }
            const selectedCommand = commandsForProgram.find(cmd => cmd.id === parseInt(commandId));
            if (selectedCommand) {
                explanationDiv.textContent = selectedCommand.explanation;
                if (selectedCommand.description.includes('[')) {
                    argumentInput.disabled = false;
                    argumentInput.placeholder = selectedCommand.description.split('[')[1].replace(']', '');
                    argumentInput.focus();
                } else {
                    argumentInput.disabled = true;
                    argumentInput.value = '';
                    argumentInput.placeholder = 'Kein Argument benötigt';
                }
            }
        });

        // Syntax-Highlighting Funktion für Konsolen-Output
        function highlightSyntax(text) {
            let highlightedText = text;
            highlightedText = highlightedText.replace(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)/g, '<span class="highlight-ip">$1</span>');
            highlightedText = highlightedText.replace(/(Port |deny )(\d{4})/g, '$1<span class="highlight-port">$2</span>');
            highlightedText = highlightedText.replace(/(Moskau, RU)/g, '<span class="highlight-location">$1</span>');
            return highlightedText;
        }

        // Typewriter-Effekt für Konsolen-Output
        function typewriterOutput(fullText, onComplete) {
            isTyping = true;
            executeBtn.disabled = true;
            argumentInput.disabled = true;
            const lines = fullText.split('\n');
            let lineIndex = 0;
            const interval = setInterval(() => {
                if (lineIndex < lines.length) {
                    const formattedLine = highlightSyntax(lines[lineIndex]);
                    consoleOutput.innerHTML += formattedLine + '<br>';
                    consoleContainer.scrollTop = consoleContainer.scrollHeight;
                    lineIndex++;
                } else {
                    clearInterval(interval);
                    isTyping = false;
                    executeBtn.disabled = false;
                    if (dropdown.value !== "" && dropdown.options[dropdown.selectedIndex].text.includes('[')) {
                        argumentInput.disabled = false;
                        argumentInput.focus();
                    }
                    if (onComplete) onComplete();
                }
            }, 300);
        }

        // Typewriter-Effekt für Hinweise im Hint-Display
        function typewriterForHints(linesArray) {
            let hintIndex = 0;
            const hintInterval = setInterval(() => {
                if (hintIndex < linesArray.length) {
                    // hintDisplay.innerHTML = ""; // Diese Zeile entfernt vorherige Hinweise, wurde im vorherigen Code dupliziert
                    hintDisplay.innerHTML += `<div class="system-notification">${linesArray[hintIndex]}</div><br>`;
                    hintIndex++;
                } else {
                    clearInterval(hintInterval);
                }
            }, 400);
        }

        // Funktion zum Ausführen von Befehlen
        function executeCommand() {
            if (isTyping) return;
            const commandId = dropdown.value;
            if (commandId === "") return;
            const selectedCommand = commandsForProgram.find(cmd => cmd.id === parseInt(commandId));
            if (!selectedCommand) return;

            const argument = argumentInput.disabled ? '' : argumentInput.value.trim();
            const fullCommandForDisplay = argument ? selectedCommand.description.replace(/\[.*\]/, argument) : selectedCommand.description;

            consoleOutput.innerHTML += `<br><span class="prompt">> ${fullCommandForDisplay}</span><br>`;
            argumentInput.value = '';

            const formData = new FormData();
            formData.append('command_id', commandId);
            formData.append('argument', argument);

            fetch('api_console.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const allLines = data.output.split('\n');
                    const consoleLines = [];
                    const hintLines = [];
                    if (data.output) {
                        hintDisplay.innerHTML = "";
                    } // Clear hint display when new output arrives
                    allLines.forEach(line => {
                        const trimmedLine = line.trim();
                        if (trimmedLine.startsWith('[HINWEIS]') || trimmedLine.startsWith('[ALARM]') || trimmedLine.startsWith('[SYSTEM]')) {
                            hintLines.push(trimmedLine);
                        } else {
                            consoleLines.push(line);
                        }
                    });
                    const consoleText = consoleLines.join('\n');

                    typewriterOutput(consoleText, () => {
                        if (data.failure_hint) {
                            hintLines.push(data.failure_hint);
                        }
                        if (hintLines.length > 0) {
                            typewriterForHints(hintLines);
                        }
                        if (data.unlocked_program) {
                            const newProg = data.unlocked_program;
                            const newBtn = document.createElement('button');
                            newBtn.className = 'program-btn new-item-blink';
                            newBtn.dataset.id = newProg.id;
                            newBtn.title = newProg.description;
                            newBtn.textContent = newProg.name;
                            toolbox.appendChild(newBtn);
                        }
                        if (selectedCommand.keyword === 'sudo ufw deny' && data.failure_hint === null) {
                            nextLevelArrow.classList.remove('hidden');
                        }
                        consoleOutput.innerHTML += `<br>`;
                        consoleContainer.scrollTop = consoleContainer.scrollHeight;
                    });
                });
        }

        executeBtn.addEventListener('click', executeCommand);
        argumentInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !isTyping) {
                e.preventDefault();
                executeCommand();
            }
        });

        // ==================================================================
        // Modal-spezifische Event Listener
        // ==================================================================

        // Öffnet das Modal beim Klick auf den Pfeil "Mission abgeschlossen..."
        document.addEventListener('click', function(e) {
            if (e.target.closest('#next-level-arrow')) {
                e.preventDefault();
                currentPage = 0; // Immer auf der ersten Seite starten beim Öffnen des Modals
                showCurrentModalPage(); // Zeigt die erste Seite an und initialisiert die Navigation
                modalOverlay.classList.remove('hidden'); // Macht das Modal-Overlay sichtbar
            }
        });

        // Event Listener für die Navigationspfeile im Modal
        modalPrevBtn.addEventListener('click', () => {
            if (currentPage > 0) {
                currentPage--;
                showCurrentModalPage();
            }
        });

        modalNextBtn.addEventListener('click', () => {
            if (currentPage < modalPages.length - 1) {
                currentPage++;
                showCurrentModalPage();
            }
        });

        // Schließt das Modal, wenn auf das Overlay selbst geklickt wird (nicht auf den Inhalt)
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                modalOverlay.classList.add('hidden');
            }
        });

        // Event Listener für den "Nächste Mission" Button im Modal
        continueBtn.addEventListener('click', function() {
            window.location.href = 'dashboard.php';
        });
    </script>
</body>

</html>