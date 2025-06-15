<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Agentenjagd – Startseite</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f4f4f4;
            color: #222;
            margin: 0;
            padding: 20px;
        }
        #container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        h1 {
            text-align: center;
        }
        a.button {
            display: block;
            text-align: center;
            padding: 12px;
            margin: 15px 0;
            background-color: #007acc;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
        }
        a.button:hover {
            background-color: #005d99;
        }
    </style>
</head>
<body>
    <div id="container">
        <h1>🔍 Agentenjagd</h1>
        <p>Willkommen zum Real-Life-Ortungsspiel <strong>Agentenjagd</strong>! Ein Spieler übernimmt die geheime Rolle des 🕵️ Agenten und versucht, unentdeckt zu entkommen, während die Detektive ihn mithilfe ihrer GPS-Standorte aufspüren.</p>

        <h2>Spiel starten:</h2>
        <a href="login.php" class="button">🔐 Einloggen</a>
        <a href="map.php" class="button">🗺️ Zur Spielkarte</a>

        <h2>Spielregeln:</h2>
        <ul>
            <li>Nur eingeloggte Spieler können Züge machen.</li>
            <li>Die Detektive sehen den Agenten nur in bestimmten Runden oder bei Sichtkontakt.</li>
            <li>Bewegung erfolgt über reale Ortsveränderung, gekoppelt an definierte Knotenpunkte.</li>
            <li>Jede Runde darf nur ein Ticket (🚶 zu Fuß, 🚴 Fahrrad, 🎟️ Spezial) genutzt werden.</li>
        </ul>
        <p>Wenn du Detektiv bist: Halte Ausschau und nutze deine Tickets klug!</p>
        <p>Wenn du Agent bist: Versuche, unentdeckt zu bleiben und die Detektive zu täuschen!</p>
    </div>
</body>
</html>
