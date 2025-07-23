<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php';

// Hier könnten wir später Logik hinzufügen, um zu prüfen,
// ob der User dieses Level schon "durchgespielt" hat oder welche Aufgaben hier noch warten.
// Für den Start ist es nur die Anzeige des Tools.

// Die Phishing-Analyse-Daten könnten auch hier in die Seite geladen werden,
// wenn die E-Mail-ID als Parameter übergeben wird (z.B. level2_phishing.php?email_id=3)
// Für dieses Beispiel nutzen wir die bereits im JS definierte Struktur.

// Simulierte Phishing-Analyse-Daten (müssen hier wiederholt oder via API geladen werden)
// Da dies ein eigenständiges Level ist, sollten die Daten hier direkt verfügbar sein
// oder über eine API abgerufen werden, die die email_id des Phishing-Versuchs kennt.
// Für Einfachheit, definieren wir sie hier statisch neu, da dies das "Ziel" ist.
$phishingAnalysisDataJson = '{
    "ip_address": "91.241.72.126",
    "domain": "mail21-126.srv2.de",
    "netname": "NET-VK-OPTIMIZELY-85",
    "country": "RU",
    "org": "ORG-0617-RIPE",
    "phone": "+7.495.1234567",
    "address": "Bolshaya Dmitrovka, 23, Moscow, Russia",
    "header_snippet": "Received: from unknown (HELO mail.ru) by scam-server.ru with ESMTPSA",
    "sha256_hash": "d41280f507b56d82250965c2763320f2b2b1a3e62f9a2e6e23b2b1b3b2b1b2b1",
    "simulated_phishing_url": "http://secure-bnd-portal.ru/direktive.php",
    "whois_full_output": "<pre>--- WHOIS 91.241.72.126 ---\ninetnum:        91.241.72.0 - 91.241.72.255\nnetname:        NET-VK-OPTIMIZELY-85\ndescr:          Optimizely GmbH Hosting Infrastructure\ncountry:        RU &lt;span class=\"red-flag\"&gt;(WARNUNG: Ursprung Russland!)&lt;/span&gt;\norg:            ORG-0617-RIPE\nadmin-c:        PAS1N-RIPE\ntech-c:         OT612-RIPE\nstatus:         ASSIGNED PI\nmnt-by:         RIPE-NCC-END-MNT\ncreated:        2023-07-17T08:56:17Z\nlast-modified:  2024-07-09T08:56:17Z\nsource:         RIPE\n\nrole:           Optimizely Technical Contact\naddress:        Bolshaya Dmitrovka, 23, Moscow, Russia\nphone:          +7.495.1234567 &lt;span class=\"red-flag\"&gt;(WARNUNG: Russische Telefonnummer!)&lt;/span&gt;\ne-mail:         abuse@optim.ru\nmnt-by:         RIPE-NCC-END-MNT\ncreated:        2022-01-01T00:00:00Z\nlast-modified:  2024-06-01T00:00:00Z\nsource:         RIPE\n\n</pre>"
}';
$phishingAnalysisData = json_decode($phishingAnalysisDataJson, true);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Level 2: Phishing-Analyse-Tool</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="level1.css"> <link rel="stylesheet" href="level2.css"> </head>

<body>
    <div class="header">
        <h1>Level 2: Phishing-Analyse-Tool</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="emails.php">Zurück zum Posteingang</a>
            <a href="logout.php">Ausloggen</a>
        </div>
    </div>

    <div class="container" style="display: flex; justify-content: center; align-items: center; height: calc(100vh - 80px); padding: 0;">
        <div id="phishing-analysis-modal-content" style="
            position: static; /* Nicht fixed, da es die Hauptansicht ist */
            transform: none; /* Keine Translation, da es nicht zentriert werden muss als Overlay */
            margin: 20px; /* Abstand zum Rand */
            height: auto; /* Höhe flexibel an Inhalt anpassen */
            width: 95%; /* Breit machen */
            max-width: 1200px; /* Aber nicht zu breit */
            min-height: calc(100vh - 120px); /* Mindesthöhe für das Panel */
            border: 1px solid #00ff7f; /* Grüner Rahmen */
            box-shadow: 0 0 30px rgba(0, 255, 127, 0.5); /* Grüner Schatten */
        ">
            <h2>Phishing-Analyse-Bericht</h2>
            
            <div class="analysis-section">
                <div class="analysis-panel">
                    <h3>E-Mail-Header & Herkunft</h3>
                    <p><strong>Von:</strong> <span>Bundeskanzleramt &lt;kanzler@bundeskanzleramt.de&gt;</span></p>
                    <p><strong>Betreff:</strong> <span>Wichtige Anweisung vom Bundeskanzleramt</span></p>
                    <p><strong>Original IP:</strong> <span id="modal-origin-ip-level2" class="highlight-ip" title="Klicken für Whois-Info"><?php echo htmlspecialchars($phishingAnalysisData['ip_address']); ?></span></p>
                    <div id="modal-whois-info-level2">
                        <p class="red-flag">Klicken Sie auf die IP-Adresse für Whois-Informationen.</p>
                    </div>
                    <p><strong>Header-Snippet:</strong> <span><?php echo htmlspecialchars($phishingAnalysisData['header_snippet']); ?></span></p>
                    <p class="red-flag">Die Ursprungs-IP-Adresse verweist auf einen Server in Russland. Die Domain `.ru` ist ebenfalls ein Warnsignal!</p>
                </div>
                
                <div class="analysis-panel">
                    <h3>Analyse der Bedrohung</h3>
                    <p><strong>Verdächtiger Link/Aktion:</strong> <a id="modal-phishing-link-level2" href="<?php echo htmlspecialchars($phishingAnalysisData['simulated_phishing_url']); ?>" target="_blank" class="red-flag"><?php echo htmlspecialchars($phishingAnalysisData['simulated_phishing_url']); ?></a></p>
                    <p class="info-text">Dieser Link führt zu einer externen, nicht vertrauenswürdigen Domäne. Der Inhalt soll den Benutzer zum Download einer "strengen Direktive" verleiten.</p>
                    <p><strong>Simulierter SHA256-Hash der Datei:</strong> <span id="modal-sha256-hash-level2" class="highlight-sha"><?php echo htmlspecialchars($phishingAnalysisData['sha256_hash']); ?></span></p>
                    <p class="info-text">Ein Hash wie dieser (realistisch: viel komplexer als dieser Platzhalter) wäre der digitale Fingerabdruck der heruntergeladenen Datei. Er kann auf bekannte Malware hinweisen, wie z.B. Spionagesoftware, die einen SSL-Tunnel zum Abfangen von Daten öffnet.</p>
                    <p class="red-flag"><strong>Gefahr:</strong> Potenzielle Spionagesoftware oder Ransomware.</p>
                </div>
            </div>

            <p class="info-text">
                **Merke:** Achten Sie immer auf Absenderadressen, Linkziele und unerwartete Dateianhänge. Hovern Sie über Links, um das tatsächliche Ziel zu sehen, bevor Sie klicken. Echte Regierungsdokumente werden niemals auf diese Weise verteilt.
            </p>

            <div style="text-align: center; margin-top: 30px;">
                <a href="dashboard.php" class="modal-close-btn">Zurück zum Dashboard</a>
                </div>
        </div>
    </div>

    <script>
        const modalOriginIpLevel2 = document.getElementById('modal-origin-ip-level2');
        const modalWhoisInfoLevel2 = document.getElementById('modal-whois-info-level2');

        // Phishing Analyse Daten direkt in JS wiederholen, oder über eine API laden wenn dynamisch
        const phishingAnalysisData = <?php echo json_encode($phishingAnalysisData); ?>;

        // Event Listener für den Klick auf die IP-Adresse im Modal (simulierte Whois)
        modalOriginIpLevel2.addEventListener('click', function() {
            if (phishingAnalysisData && phishingAnalysisData.whois_full_output) {
                modalWhoisInfoLevel2.innerHTML = phishingAnalysisData.whois_full_output; // HTML-Inhalt direkt einfügen
            } else {
                modalWhoisInfoLevel2.innerHTML = `<p class="red-flag">Keine Whois-Informationen verfügbar.</p>`;
            }
        });
    </script>
</body>

</html>