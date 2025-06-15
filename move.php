<?php
session_start();
require_once("config.php");

header('Content-Type: application/json');

$player_id = $_SESSION['player_id'] ?? null;
if (!$player_id) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$punkt_nr = $_POST['location_id'] ?? null;
$ticket_type = $_POST['ticket_type'] ?? null;
$current_lat = $_POST['current_lat'] ?? null;
$current_lng = $_POST['current_lng'] ?? null;
$gps_distance = $_POST['distance'] ?? null; // Optional übergeben


if (!$punkt_nr || !$ticket_type || !$current_lat || !$current_lng) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Daten']);
    exit;
}

// Spielerinfos laden
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$player_id]);
$player = $stmt->fetch();
if (!$player) {
    echo json_encode(['success' => false, 'message' => 'Spieler nicht gefunden']);
    exit;
}

// Aktuelle Runde
$stmt = $pdo->query("SELECT current_round, gps_tolerance FROM game_status WHERE id = 1");
$game_status = $stmt->fetch();
$current_round = (int)$game_status['current_round'];
$gps_tolerance = (float)$game_status['gps_tolerance'];													  

// Hat Spieler schon gezogen?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movements WHERE player_id = ? AND round = ?");
$stmt->execute([$player_id, $current_round]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Du hast in dieser Runde schon gezogen']);
    exit;
}

// Detektive dürfen nur ziehen, wenn Mr. X schon gezogen hat
if ($player['role'] != 'mr_x') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movements m JOIN players p ON m.player_id = p.id WHERE m.round = ? AND p.role = 'mr_x'");
    $stmt->execute([$current_round]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Mr. X muss zuerst ziehen.']);
        exit;
    }
}

// Mr. X muss zuerst ziehen
if ($player['role'] == 'mr_x') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movements WHERE round = ? AND player_id != ?");
    $stmt->execute([$current_round, $player_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Mr. X muss zuerst ziehen.']);
        exit;
    }
}

// Letzter Standort bestimmen
$stmt = $pdo->prepare("SELECT location_id FROM movements WHERE player_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$player_id]);
$current_location_id = $stmt->fetchColumn();

if (!$current_location_id) {
																	     // Wenn noch kein Move gemacht wurde: Standort aus players nutzen
    $stmt = $pdo->prepare("SELECT id FROM locations WHERE lat = ? AND lng = ?");
    $stmt->execute([$player['last_lat'], $player['last_lng']]);
    $current_location_id = $stmt->fetchColumn();

    if (!$current_location_id) {
        echo json_encode(['success' => false, 'message' => 'Aktueller Standort unbekannt']);
        exit;
    }
}

// Punktdaten laden
$stmt = $pdo->prepare("SELECT punkt_nr, city_id FROM locations WHERE id = ?");
$stmt->execute([$current_location_id]);
$from = $stmt->fetch();

$stmt = $pdo->prepare("SELECT punkt_nr, id, lat, lng FROM locations WHERE city_id = ? AND punkt_nr = ?");
$stmt->execute([$from['city_id'], $punkt_nr]);
$to = $stmt->fetch();

if (!$from || !$to) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Punkte']);
    exit;
}


// Verbindung prüfen
$sql = "SELECT COUNT(*) FROM connections WHERE city_id = ? AND allowed_ticket = ? AND ((from_punkt = ? AND to_punkt = ?) OR (to_punkt = ? AND from_punkt = ?))";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $from['city_id'],
    $ticket_type,
    $from['punkt_nr'], $to['punkt_nr'],
    $from['punkt_nr'], $to['punkt_nr']
]);
if ($stmt->fetchColumn() == 0) {
    echo json_encode(['success' => false, 'message' => 'Keine gültige Verbindung']);
    exit;
}

// Ticket prüfen
$stmt = $pdo->prepare("SELECT remaining FROM player_tickets WHERE player_id = ? AND ticket_type = ?");
$stmt->execute([$player_id, $ticket_type]);
if ($stmt->fetchColumn() <= 0) {
    echo json_encode(['success' => false, 'message' => 'Kein Ticket mehr übrig']);
    exit;
}

// Standortprüfung, wenn aktiviert
if ($player['constrain_location']) {
    // Toleranz aus game_status holen (in Metern)
    $stmt = $pdo->query("SELECT gps_tolerance FROM game_status WHERE id = 1");
    $tolerance = (float)$stmt->fetchColumn();

    // Wenn Distanz schon von map.php übergeben wurde – diese verwenden
    if ($gps_distance !== null) {
        if ($gps_distance > $tolerance) {
            echo json_encode(['success' => false, 'message' => '📍 Standortabweichung laut Gerät: ' . round($gps_distance) . ' m (Toleranz: ' . $tolerance . ' m)']);
            exit;
        }
    } elseif ($current_lat !== null && $current_lng !== null) {
        // Alternativ berechnen
        function haversine($lat1, $lon1, $lat2, $lon2) {
            $earthRadius = 6371000; // Meter
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                 sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            return $earthRadius * $c;
        }

        $distanz = haversine($current_lat, $current_lng, $player['last_lat'], $player['last_lng']);
        if ($distanz > $tolerance) {
            echo json_encode(['success' => false, 'message' => '📍 Standortabweichung berechnet: ' . round($distanz) . ' m (Toleranz: ' . $tolerance . ' m)']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => '📍 Keine Standortdaten zur Prüfung erhalten']);
        exit;
    }
}

// Bewegung speichern
$stmt = $pdo->prepare("INSERT INTO movements (player_id, location_id, ticket_type, lat, lng, round) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $player_id,
    $to['id'],
    $ticket_type,
    $to['lat'],
    $to['lng'],
    $current_round
]);

// Position updaten
$stmt = $pdo->prepare("UPDATE players SET last_lat = ?, last_lng = ?, last_update = NOW() WHERE id = ?");
$stmt->execute([$to['lat'], $to['lng'], $player_id]);

// Ticket abziehen
$stmt = $pdo->prepare("UPDATE player_tickets SET remaining = remaining - 1 WHERE player_id = ? AND ticket_type = ?");
$stmt->execute([$player_id, $ticket_type]);

// Runde erhöhen wenn alle Spieler gezogen haben
$stmt = $pdo->query("SELECT COUNT(*) FROM players");
$total_players = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT player_id) FROM movements WHERE round = ?");
$stmt->execute([$current_round]);
$moved_players = $stmt->fetchColumn();

if ($moved_players >= $total_players) {
    $pdo->prepare("UPDATE game_status SET current_round = current_round + 1, last_change = NOW() WHERE id = 1")->execute();
}

echo json_encode(['success' => true, 'message' => 'Zug durchgeführt']);
?>
