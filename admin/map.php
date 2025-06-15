<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once('../config.php');

// aktuelle Spielerpositionen abrufen
$stmt = $pdo->query("SELECT name, role, last_lat, last_lng, visible FROM players WHERE last_lat IS NOT NULL AND last_lng IS NOT NULL");
$players = $stmt->fetchAll();

// aktuelle Runde
$roundStmt = $pdo->query("SELECT current_round FROM game_status WHERE id = 1");
$round = $roundStmt->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Live-Karte – Scotland Yard</title>
    <style>
        #map { height: 90vh; width: 100%; }
        body { font-family: sans-serif; margin: 0; }
        .info {
            background: #f0f0f0;
            padding: 10px;
            font-size: 1.1em;
            border-bottom: 1px solid #ccc;
        }
    </style>
</head>
<body>

<?php include('menu.php'); ?>

<div class="info">
    📍 Live-Spielerkarte – Runde <?= htmlspecialchars($round) ?>
</div>

<div id="map"></div>

<script>
let map;
let markers = [];
let polylines = [];

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 51.124, lng: 6.290 },
        zoom: 14
    });

    loadMapData();
    setInterval(loadMapData, 30000); // alle 30 Sekunden neu
}

function loadMapData() {
    fetch('map_data.php')
        .then(res => res.json())
        .then(players => {
            // vorherige Marker/Polylines löschen
            markers.forEach(m => m.setMap(null));
            polylines.forEach(l => l.setMap(null));
            markers = [];
            polylines = [];

            players.forEach(p => {
                let isMrXHidden = p.role === 'mr_x' && !p.visible;
				const current = { lat: parseFloat(p.last_lat), lng: parseFloat(p.last_lng) };
				const marker = new google.maps.Marker({
					position: current,
					map: map,
					title: `${p.name} (${p.role}${isMrXHidden ? ' – versteckt' : ''})`,
					icon: isMrXHidden
						? "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
						: (p.role === 'mr_x'
							? "http://maps.google.com/mapfiles/ms/icons/black-dot.png"
							: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png")
				});


         
                markers.push(marker);

                const info = new google.maps.InfoWindow({
					content: `<strong>${p.name}</strong><br>Rolle: ${p.role}${isMrXHidden ? ' (versteckt)' : ''}`
				});

                marker.addListener("click", () => info.open(map, marker));

                // Wenn vorherige Position vorhanden → Linie mit Richtung
                if (p.previous_position) {
                    try {
                        const prev = JSON.parse(p.previous_position);
                        const line = new google.maps.Polyline({
                            path: [prev, current],
                            geodesic: true,
                            strokeColor: "#FF0000",
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            icons: [{
                                icon: { path: google.maps.SymbolPath.FORWARD_OPEN_ARROW },
                                offset: '100%'
                            }],
                            map: map
                        });
                        polylines.push(line);
                    } catch (e) {
                        console.warn("Fehler beim Parsen vorheriger Position für " + p.name);
                    }
                }
            });
        });
}
</script>


<script
  src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>&callback=initMap"
  async defer>
</script>


</body>
</html>
