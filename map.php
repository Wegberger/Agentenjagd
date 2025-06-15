<?php
session_start();
require_once("config.php");

if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Scotland Yard – Karte</title>
    <style>
        html, body { height: 100%; margin: 0; }
        #map { height: 100%; }
        #infoBox {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 999;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 0 5px #888;
            font-family: sans-serif;
            font-size: 14px;
        }
        #navLinks {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 999;
            font-family: sans-serif;
            font-size: 14px;
        }
        #navLinks a {
            margin-left: 10px;
            text-decoration: none;
        }
    </style>
	<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>"></script>

</head>
<body>
<div id="infoBox">
    <strong>🔍 Spielerinfo</strong><br>
    👤 <span id="infoName">-</span><br>
    🎭 <span id="infoRole">-</span><br>
    🕑 Runde: <span id="infoRound">-</span><br>
	<span id="waitForMrX" style="color: red;"></span><br>
    💾 Tickets:<br>
    🚶 Walk: <span id="ticketWalk">-</span><br>
    🚴 Bike: <span id="ticketBike">-</span><br>
    🎟️ Spezial: <span id="ticketSpecial">-</span><br>
	📍 Standortabweichung: <span id="distanceInfo">-</span><br>
</div>

<div id="navLinks">
    <a href="login.php">🔐 Login</a>
    <a href="logout.php">🚪 Logout</a>
</div>
<div id="map"></div>

<script>
let map;
let playerMarkers = {};
let moveMarkers = [];
let locationMarkers = [];
let connectionLines = [];

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: {lat: 51.135, lng: 6.28},
        zoom: 14
    });
    loadMapData();
    setInterval(loadMapData, 10000);
}

function getDistance(lat1, lng1, lat2, lng2) {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lng2 - lng1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

async function loadMapData() {
    const res = await fetch('map_data.php');
    const data = await res.json();

    const me = data.self;

     
	updateDistanceInfo(parseFloat(me.last_lat), parseFloat(me.last_lng));
    const round = data.round;
    const players = data.players;
    const moves = data.moves;
    const locations = data.locations;
    const connections = data.connections;
	const waitMsg = document.getElementById('waitForMrX');
	const hasMovedThisRound = data.has_moved;  // boolean

	// Logik für Statusanzeige
	if (me.role === 'mr_x') {
		if (hasMovedThisRound) {
			waitMsg.textContent = "⏳ Du hast in dieser Runde schon gezogen";
		} else {
			if (data.detectives_moved === data.detectives_total) {
				waitMsg.textContent = "⏳ Du kannst ziehen";
			} else {
				waitMsg.textContent = "⏳ Detektive müssen zuerst ziehen";
			}
		}
	} else {
		if (!data.mr_x_moved) {
			waitMsg.textContent = "⏳ Mr. X ist am Zug";
		} else {
			waitMsg.textContent = hasMovedThisRound
				? "⏳ Du hast in dieser Runde schon gezogen"
				: "⏳ Du kannst ziehen";
		}
	}


    document.title = `🎲 Runde ${round} – ${me.name} (${me.role})`;
    document.getElementById('infoName').textContent = me.name;
    document.getElementById('infoRole').textContent = (me.role === 'mr_x') ? 'Mr. X' : 'Detektiv';
    document.getElementById('infoRound').textContent = round;
    document.getElementById('ticketWalk').textContent = data.tickets.walk ?? 0;
    document.getElementById('ticketBike').textContent = data.tickets.bike ?? 0;
    document.getElementById('ticketSpecial').textContent = data.tickets.special ?? 0;
	


	// Nur aktuelle Spielerpositionen zeichnen (alte Marker ersetzen)
    Object.values(playerMarkers).forEach(m => m.setMap(null));																	
    playerMarkers = {};
    players.forEach(p => {
        if (p.visible || p.id === me.id) {
            if (p.last_lat && p.last_lng) {
                const pos = {lat: parseFloat(p.last_lat), lng: parseFloat(p.last_lng)};
                const label = (p.role === 'mr_x') ? (p.visible ? '❓' : '') : '🕵️';
                const iconColor = (p.role === 'mr_x') ? 'black' : 'blue';

                playerMarkers[p.id] = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: `${p.name} (${p.role})`,
                    label: label,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 12,
                        fillColor: iconColor,
                        fillOpacity: 1.0,
                        strokeWeight: 1,
                        strokeColor: "#fff"
                    }
                });
            }
        }
    });

    moveMarkers.forEach(m => m.setMap(null));
    locationMarkers.forEach(m => m.setMap(null));
    connectionLines.forEach(l => l.setMap(null));
    moveMarkers = [];
    locationMarkers = [];
    connectionLines = [];

    const currentPos = {lat: parseFloat(me.last_lat), lng: parseFloat(me.last_lng)};

    moves.forEach(m => {
        const pos = {lat: parseFloat(m.lat), lng: parseFloat(m.lng)};
        const distance = getDistance(currentPos.lat, currentPos.lng, pos.lat, pos.lng);

        const marker = new google.maps.Marker({
            position: pos,
            map: map,
            title: `Punkt ${m.to_punkt} (${m.ticket})`,
            icon: {
                path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                scale: 10,
                fillColor: getColorForTicket(m.ticket),
                fillOpacity: 0.7,
                strokeWeight: 1,
                strokeColor: "#000"
            }
        });

        if (me.constrain_location === "1" && distance > 200) {
            marker.setOpacity(0.3);
        } else {
            marker.addListener('click', () => {
                doMove(m.to_punkt, m.ticket);
            });
        }
        moveMarkers.push(marker);

        const line = new google.maps.Polyline({
            path: [currentPos, pos],
            geodesic: true,
            strokeColor: getColorForTicket(m.ticket),
            strokeOpacity: 1.0,
            strokeWeight: 3,
            map: map
        });
        connectionLines.push(line);
    });

    locations.forEach(loc => {
        const marker = new google.maps.Marker({
            position: {lat: parseFloat(loc.lat), lng: parseFloat(loc.lng)},
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 6,
                fillColor: "#888",
                fillOpacity: 0.6,
                strokeWeight: 0
            },
            title: `Punkt ${loc.punkt_nr}`
        });
        locationMarkers.push(marker);
    });

    connections.forEach(conn => {
        const line = new google.maps.Polyline({
            path: [
                {lat: parseFloat(conn.from_lat), lng: parseFloat(conn.from_lng)},
                {lat: parseFloat(conn.to_lat), lng: parseFloat(conn.to_lng)}
            ],
            geodesic: true,
            strokeColor: "#888",
            strokeOpacity: 0.8,
            strokeWeight: 3,
            zIndex: 1,
            map: map
        });
        connectionLines.push(line);
    });
}

function getColorForTicket(type) {
    switch (type) {
        case 'walk': return 'green';
        case 'bike': return 'blue';
        case 'special': return 'purple';
        default: return 'gray';
    }
}
async function doMove(location_id, ticket_type) {
    if (!navigator.geolocation) {
        alert("Standortzugriff nicht möglich.");
        return;
    }

    navigator.geolocation.getCurrentPosition(async position => {
        const currentLat = position.coords.latitude;
        const currentLng = position.coords.longitude;

        const playerMarker = playerMarkers[<?php echo $_SESSION['player_id']; ?>];
        if (!playerMarker) {
            alert("Spielerposition nicht bekannt.");
            return;
        }

        const playerLat = playerMarker.getPosition().lat();
        const playerLng = playerMarker.getPosition().lng();
        const distance = calculateDistance(currentLat, currentLng, playerLat, playerLng);

        const body = `location_id=${encodeURIComponent(location_id)}&ticket_type=${encodeURIComponent(ticket_type)}&current_lat=${currentLat}&current_lng=${currentLng}&distance=${distance}`;

        const response = await fetch('move.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body
        });

        const result = await response.json();
        if (result.success) {
            alert("Zug erfolgreich!");
            loadMapData();
        } else {
            alert("❌ Fehler beim Zug: " + result.message + `\n(Abweichung: ${Math.round(distance)} m)`);
        }
    }, () => {
        alert("Standortermittlung fehlgeschlagen.");
    });
}




function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Meter
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function updateDistanceInfo(playerLat, playerLng) {
    if (!navigator.geolocation) {
        document.getElementById('distanceInfo').textContent = 'GPS nicht verfügbar';
        return;
    }

    navigator.geolocation.getCurrentPosition(position => {
        const myLat = position.coords.latitude;
        const myLng = position.coords.longitude;
        const dist = calculateDistance(myLat, myLng, playerLat, playerLng);
        document.getElementById('distanceInfo').textContent = Math.round(dist) + " m";
    }, error => {
        document.getElementById('distanceInfo').textContent = 'Keine Freigabe';
    });
}


window.onload = initMap;
</script>
</body>
</html>
