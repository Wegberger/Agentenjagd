<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once('../config.php');

// Städte abrufen
$stmt = $pdo->query("SELECT * FROM cities ORDER BY name");
$cities = $stmt->fetchAll();

$currentCityId = isset($_GET['city']) ? intval($_GET['city']) : ($cities[0]['id'] ?? null);

// Marker abrufen
$markers = [];
if ($currentCityId) {
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE city_id = ?");
    $stmt->execute([$currentCityId]);
    $markers = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM connections WHERE city_id = ?");
    $stmt->execute([$currentCityId]);
    $connections = $stmt->fetchAll();
} else {
    $connections = [];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Knotenpunkte verwalten</title>
  <style>
    body { font-family: sans-serif; margin: 20px; }
    select, input, button { font-size: 1em; margin: 5px; }
    #map { height: 80vh; width: 100%; margin-top: 10px; }
  </style>
</head>
<body>

<?php include('menu.php'); ?>
<h2>📍 Knotenpunkte verwalten</h2>
<form method="get" action="">
  <label>Stadt wählen:
    <select name="city" onchange="this.form.submit()">
      <?php foreach ($cities as $city): ?>
        <option value="<?= $city['id'] ?>" <?= $city['id'] == $currentCityId ? 'selected' : '' ?>>
          <?= htmlspecialchars($city['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
</form>

<form method="post" action="save_city.php">
  <input name="name" placeholder="Neue Stadt" required>
  <input name="lat" placeholder="Lat" required>
  <input name="lng" placeholder="Lng" required>
  <button type="submit">➕ Stadt hinzufügen</button>
</form>
<label><input type="checkbox" id="enableDrag"> 🖱️ Marker verschiebbar</label>
<div id="map"></div>

<h3>➕ Verbindung anlegen</h3>
<form id="connectionForm">
  <label>Von Punkt: <input type="number" id="from" required></label>
  <label>Nach Punkt: <input type="number" id="to" required></label>
  <label>Ticket:
    <select id="ticket" required>
      <option value="walk">Zu Fuß</option>
      <option value="bike">Fahrrad</option>
      <option value="special">Spezial</option>
      <option value="black">Schwarz</option>
    </select>
  </label>
  <button type="submit">Verbindung speichern</button>
</form>

<script>
let map;
const markers = [];
const connectionLines = [];
let punktCounter = <?= count($markers) + 1 ?>;

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: {
            lat: <?= $cities[array_search($currentCityId, array_column($cities, 'id'))]['center_lat'] ?? 51.124 ?>,
            lng: <?= $cities[array_search($currentCityId, array_column($cities, 'id'))]['center_lng'] ?? 6.290 ?>
        },
        zoom: 14
    });

    const saved = <?= json_encode($markers) ?>;
    saved.forEach(p => addMarker(p.lat, p.lng, p.punkt_nr, p.name, p.id));

    drawConnections();

    map.addListener("click", (e) => {
        const name = prompt("Name für Punkt " + punktCounter + ":");
        if (!name) return;
        saveLocation(punktCounter, name, e.latLng.lat(), e.latLng.lng(), null);
        addMarker(e.latLng.lat(), e.latLng.lng(), punktCounter, name);
        punktCounter++;
    });
}

function addMarker(lat, lng, punkt, name, id = null) {
    const isDraggable = document.getElementById('enableDrag')?.checked;

    const marker = new google.maps.Marker({
        position: { lat, lng },
        map,
        draggable: isDraggable,
        title: `${punkt}: ${name}`
    });

    marker.punkt = punkt;
    marker._locationInfo = { id, punkt, name };

    if (isDraggable) {
        marker.addListener("dragend", function () {
            const pos = marker.getPosition();
            saveLocation(punkt, name, pos.lat(), pos.lng(), marker._locationInfo.id);
        });
    }

    markers.push(marker);
}


function saveLocation(punkt, name, lat, lng, id = null) {
    fetch('save_location.php', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ punkt_nr: punkt, name, lat, lng, city_id: <?= $currentCityId ?>, id })
    }).then(res => res.text()).then(console.log);
}

function drawConnections() {
    const cons = <?= json_encode($connections) ?>;
    cons.forEach(c => {
        const from = markers.find(m => m.punkt === c.from_punkt);
        const to = markers.find(m => m.punkt === c.to_punkt);
        if (!from || !to) return;
        const line = new google.maps.Polyline({
            path: [from.position, to.position],
            strokeColor: {
                walk: "#00aa00",
                bike: "#0000cc",
                special: "#aa00aa",
                black: "#000000"
            }[c.allowed_ticket] || "#999",
            strokeOpacity: 0.7,
            strokeWeight: 3,
            map
        });
        connectionLines.push(line);
    });
}

document.getElementById('connectionForm').addEventListener('submit', e => {
    e.preventDefault();
    const from = parseInt(document.getElementById('from').value);
    const to = parseInt(document.getElementById('to').value);
    const ticket = document.getElementById('ticket').value;

    fetch('save_connection.php', {
        method: 'POST',
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
			from_punkt: from,
			to_punkt: to,
			ticket,
			city_id: <?= $currentCityId ?>
		})
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        location.reload();
    });
});
</script>

<script
  src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>&callback=initMap"
  async defer>
</script>


</body>
</html>
