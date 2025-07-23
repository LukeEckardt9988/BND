<?php
// Ein einfacher Browser, der HTML-Dateien aus dem 'pages'-Verzeichnis lädt.

// Sicherheits-Check: Nur erlaubte Zeichen im Dateinamen
$initial_page = $_GET['page'] ?? 'start.html';
if (!preg_match('/^[a-zA-Z0-9_\-]+\.html$/', $initial_page)) {
    $initial_page = 'start.html'; // Fallback bei ungültigem Dateinamen
}

// Pfad zum pages-Verzeichnis relativ zur browser.php
$pages_dir_relative = 'pages/';
// Die initiale URL für den Iframe
$initial_iframe_src = $pages_dir_relative . $initial_page;

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>BND Intranet Browser</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            flex-direction: column;
        }

        .browser-toolbar {
            display: flex;
            align-items: center;
            padding: 5px;
            background-color: #2a2a2a;
            border-bottom: 1px solid #333;
            gap: 5px;
            flex-shrink: 0;
        }

        #back-btn,
        #go-btn,
        #fav-btn,
        #fav-list-btn {
            padding: 5px 10px;
            background-color: #444;
            color: #e0e0e0;
            border: 1px solid #555;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }

        #back-btn:hover,
        #go-btn:hover,
        #fav-btn:hover,
        #fav-list-btn:hover {
            background-color: #555;
        }

        #url-bar {
            flex-grow: 1;
            padding: 5px 10px;
            border: 1px solid #444;
            border-radius: 3px;
            background-color: #111;
            color: #00ff7f;
            font-size: 0.9em;
            outline: none;
        }

        #browser-iframe {
            flex-grow: 1;
            width: 100%;
            border: none;
            background-color: #0d0d0d;
        }
    </style>
</head>

<body>
    <div class="browser-toolbar">
        <button id="back-btn">&larr;</button>
        <input type="text" id="url-bar" value="<?php echo htmlspecialchars('browser.php?page=' . $initial_page); ?>">
        <button id="go-btn">Go</button>
        <button id="fav-btn">★ Fav</button>
        <button id="fav-list-btn">Lesezeichen</button>
    </div>
    <iframe id="browser-iframe" src="<?php echo htmlspecialchars($initial_iframe_src); ?>"></iframe>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlBar = document.getElementById('url-bar');
            const goBtn = document.getElementById('go-btn');
            const backBtn = document.getElementById('back-btn');
            const favBtn = document.getElementById('fav-btn');
            const favListBtn = document.getElementById('fav-list-btn');
            const browserIframe = document.getElementById('browser-iframe');

            let history = [];
            let historyIndex = -1;

            // Make loadUrl globally accessible in this window's scope
            window.loadUrl = function(url, pushToHistory = true) {
                let targetPageName = 'start.html'; // Default fallback for content page
                const pagesPrefix = 'pages/';
                const browserPhpPrefix = 'browser.php?page=';

                // 1. Determine the content page name (e.g., 'nmap.html')
                if (url.startsWith('http://') || url.startsWith('https://')) {
                    alert("Zugriff auf externe Websites ist im Intranet-Browser nicht gestattet.");
                    targetPageName = 'start.html';
                } else if (url.startsWith(browserPhpPrefix)) {
                    // Extract 'nmap.html' from 'browser.php?page=nmap.html'
                    targetPageName = url.substring(browserPhpPrefix.length);
                } else if (url.startsWith(pagesPrefix)) {
                    // If URL is already in 'pages/nmap.html' format (e.g. from history internal src)
                    targetPageName = url.substring(pagesPrefix.length);
                } else if (url.endsWith('.html') || url.endsWith('.php')) {
                    // User typed just 'nmap.html' or 'organigramm.html'
                    targetPageName = url;
                } else {
                    alert("Ungültige oder unvollständige URL. Lade Startseite.");
                    targetPageName = 'start.html';
                }

                // Basic validation for page name to prevent path traversal
                if (!/^[a-zA-Z0-9_\-.]+\.(html|php)$/.test(targetPageName)) { // Erlaubt auch Punkte im Dateinamen
                    console.warn('Invalid page name detected:', targetPageName);
                    targetPageName = 'start.html'; // Fallback for really malformed names
                }

                // 2. Construct the final SRC for the iframe (e.g., 'pages/nmap.html')
                const finalIframeSrc = pagesPrefix + targetPageName;

                // 3. Construct the URL to display in the URL bar (e.g., 'browser.php?page=nmap.html')
                const displayUrlInBar = browserPhpPrefix + targetPageName;

                // Set the iframe's source
                browserIframe.src = finalIframeSrc;

                // Update URL bar and history after the iframe content is loaded
                // This ensures that the URL bar reflects the *actual* loaded page,
                // especially important if there are redirects or hash changes within the iframe.
                browserIframe.onload = function() {
                    // It's safer to use displayUrlInBar, as iframe.contentWindow.location.href might be complex
                    // For example, it could be 'http://localhost/BND/pages/nmap.html'
                    // We want to show 'browser.php?page=nmap.html' in the bar
                    urlBar.value = displayUrlInBar;

                    // History management
                    if (pushToHistory) {
                        if (historyIndex < history.length - 1) {
                            history = history.slice(0, historyIndex + 1);
                        }
                        // Store the canonical display URL in history for consistent back navigation
                        history.push(displayUrlInBar);
                        historyIndex = history.length - 1;
                    }
                    backBtn.disabled = historyIndex <= 0;
                };

                // Also update the URL bar immediately for quicker feedback,
                // onload will then confirm/refine it
                urlBar.value = displayUrlInBar;
                backBtn.disabled = historyIndex <= 0;
            };

            // Initialen Ladevorgang auslösen und History initialisieren
            // Nutze die global zugängliche loadUrl Funktion
            window.loadUrl(urlBar.value, true);

            // Go-Button Funktionalität
            goBtn.addEventListener('click', () => {
                window.loadUrl(urlBar.value, true);
            });

            // Enter-Taste in der URL-Leiste
            urlBar.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.loadUrl(urlBar.value, true);
                }
            });

            // Zurück-Button Funktionalität
            backBtn.addEventListener('click', () => {
                if (historyIndex > 0) {
                    historyIndex--;
                    const prevUrl = history[historyIndex];
                    // Load the URL from history, and set pushToHistory to false to prevent re-adding
                    window.loadUrl(prevUrl, false);
                }
            });

            // Favoriten-Button Funktionalität (jetzt mit API-Aufruf)
            favBtn.addEventListener('click', async () => {
                const currentUrl = urlBar.value; // Dies ist bereits 'browser.php?page=nmap.html'
                // Extrahiere einen "schönen" Titel für den Favoriten, z.B. aus dem Seitentitel oder URL
                let title = browserIframe.contentDocument ? browserIframe.contentDocument.title : currentUrl;
                if (!title || title.trim() === "") { // Fallback, falls Dokumententitel leer ist
                    // Versuche, den Dateinamen zu verwenden und Endungen zu entfernen
                    let urlPart = currentUrl.split('?page=').pop();
                    title = urlPart.replace('.html', '').replace('.php', '').replace(/[-_]/g, ' ').trim();
                    if (!title) title = currentUrl; // Letzter Fallback
                }
                title = "Lesezeichen: " + title.substring(0, 50); // Kürzen und Präfix

                const formData = new FormData();
                formData.append('title', title);
                formData.append('url', currentUrl); // Speichere die browser.php?page=... URL

                try {
                    const response = await fetch('api_add_favorite.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                    } else {
                        alert(`Fehler beim Hinzufügen des Favoriten: ${result.message}`);
                    }
                } catch (error) {
                    alert('Verbindungsfehler beim Speichern des Favoriten.');
                    console.error('Error adding favorite:', error);
                }
            });

            // Lesezeichenliste-Button Funktionalität (öffnet neue Seite)
            favListBtn.addEventListener('click', () => {
                window.loadUrl('browser.php?page=favorites.php', true);
            });

            // Initialen Status des Zurück-Buttons setzen
            backBtn.disabled = true;
        });
    </script>
</body>

</html>