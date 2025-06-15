<?php
require_once("config.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['player_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$player_id = $_SESSION['player_id'];

// Spielerinformationen abrufen
$stmt = $pdo->prepare("SELECT id, name, role, last_lat, last_lng, visible, last_update, pin_code FROM players WHERE id = ?");
$stmt->execute([$player_id]);
$self = $stmt->fetch();

// Ticketdaten aus player_tickets
$tickets = ['walk' => 0, 'bike' => 0, 'special' => 0];
$stmt = $pdo->prepare("SELECT ticket_type, remaining FROM player_tickets WHERE player_id = ?");
$stmt->execute([$player_id]);
while ($row = $stmt->fetch()) {
    $tickets[$row['ticket_type']] = (int)$row['remaining'];
}

// Alle Spieler abrufen
$stmt = $pdo->query("SELECT id, name, role, last_lat, last_lng, visible FROM players");
$players = $stmt->fetchAll();

// Aktuelle Position
$current_location_id = null;
if (!empty($self['last_lat']) && !empty($self['last_lng'])) {
    $stmt = $pdo->prepare("SELECT id FROM locations WHERE ABS(lat - ?) < 0.0001 AND ABS(lng - ?) < 0.0001");
    $stmt->execute([$self['last_lat'], $self['last_lng']]);
    $row = $stmt->fetch();
    if ($row) {
        $current_location_id = $row['id'];
    }
}

// Mögliche Bewegungen
$moves = [];
if ($current_location_id) {
    $stmt = $pdo->prepare("SELECT punkt_nr, city_id FROM locations WHERE id = ?");
    $stmt->execute([$current_location_id]);
    $loc = $stmt->fetch();
    $punkt = $loc['punkt_nr'];
    $city_id = $loc['city_id'];

    error_log(json_encode(['debug' => 'city', 'city' => $city_id, 'punkt' => $punkt]));

    // Direktes SQL mit eingebauten Werten (Variante 2 – nur weil wir die Werte intern erzeugen)
    $sql = "SELECT 
                CASE WHEN from_punkt = $punkt THEN to_punkt ELSE from_punkt END AS to_punkt,
                allowed_ticket
            FROM connections
            WHERE city_id = $city_id AND (from_punkt = $punkt OR to_punkt = $punkt)";
    $stmt = $pdo->query($sql);
    $conn = $stmt->fetchAll();

    foreach ($conn as $c) {
        $stmt2 = $pdo->prepare("SELECT lat, lng FROM locations WHERE city_id = ? AND punkt_nr = ?");
        $stmt2->execute([$city_id, $c['to_punkt']]);
        $coord = $stmt2->fetch();
        if ($coord) {
            $moves[] = [
                'to_punkt' => $c['to_punkt'],
                'lat' => (float)$coord['lat'],
                'lng' => (float)$coord['lng'],
                'ticket' => $c['allowed_ticket']
            ];
        }
    }
}

// Alle Locations laden
$stmt = $pdo->query("SELECT punkt_nr, lat, lng FROM locations");
$locations = $stmt->fetchAll();

// Connections mit Koordinaten
$stmt = $pdo->query(
    "SELECT 
        l1.lat AS from_lat, l1.lng AS from_lng,
        l2.lat AS to_lat, l2.lng AS to_lng,
        c.allowed_ticket
    FROM connections c
    JOIN locations l1 ON l1.city_id = c.city_id AND l1.punkt_nr = c.from_punkt
    JOIN locations l2 ON l2.city_id = c.city_id AND l2.punkt_nr = c.to_punkt"
);
$connections = $stmt->fetchAll();

// Runden-Info aus movements statt moves
$stmt = $pdo->query("SELECT MAX(round) as max_round FROM movements");
$round = $stmt->fetchColumn();

// Sichtbarkeit von Mr. X abhängig von der Runde
foreach ($players as &$p) {
    if ($p['role'] === 'mr_x') {
        // Sichtbarkeitsrunden aus game_status lesen
		$stmt = $pdo->query("SELECT reveal_rounds FROM game_status WHERE id = 1");
		$revealStr = $stmt->fetchColumn();
		$visible_rounds = array_map('intval', explode(',', $revealStr));

        if (in_array((int)$round, $visible_rounds)) {
            $p['visible'] = 1; // Sichtbar unabhängig vom Datenbankwert
        }
        // Sonst bleibt der DB-Wert erhalten
    }
}
unset($p);



// Spieler filtern: Mr. X wird nur angezeigt, wenn sichtbar oder man selbst Mr. X ist
$filtered_players = [];
foreach ($players as $p) {
    if ($p['role'] === 'mr_x') {
        if ($p['visible'] || $p['id'] === $self['id']) {
            $filtered_players[] = $p;
        }
    } else {
        $filtered_players[] = $p;
    }
}

// Status: Hat Mr. X diese Runde schon gezogen?
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM movements 
    WHERE round = ? 
    AND player_id IN (SELECT id FROM players WHERE role = 'mr_x')
");
$stmt->execute([$round]);
$mr_x_has_moved = $stmt->fetchColumn() > 0;


$players_with_moves = $pdo->prepare("SELECT player_id FROM movements WHERE round = ?");
$players_with_moves->execute([$round]);
$moved_player_ids = array_column($players_with_moves->fetchAll(), 'player_id');

// Spieler um Bewegungsstatus ergänzen
foreach ($filtered_players as &$p) {
    $p['has_moved'] = in_array($p['id'], $moved_player_ids);
}
unset($p);

// Hat der aktuelle Spieler in dieser Runde bereits gezogen?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movements WHERE player_id = ? AND round = ?");
$stmt->execute([$player_id, $round]);
$self_has_moved = $stmt->fetchColumn() > 0;

// Bestimme ob der Spieler jetzt ziehen darf
$status_message = '';
if ($self['role'] === 'mr_x') {
    if (!$mr_x_has_moved && !$self_has_moved) {
        $status_message = '⏳ Du kannst ziehen';
    } elseif ($self_has_moved) {
        $status_message = '⏳ Du hast in dieser Runde schon gezogen';
    } else {
        $status_message = '⏳ Detektive müssen zuerst ziehen';
    }
} else { // Detektiv
    if (!$mr_x_has_moved) {
        if (!$self_has_moved) {
            $status_message = '⏳ Du kannst ziehen';
        } else {
            $status_message = '⏳ Du hast in dieser Runde schon gezogen';
        }
    } else {
        $status_message = '⏳ Mr. X ist am Zug';
    }
}
// Hat Spieler schon gezogen?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movements WHERE player_id = ? AND round = ?");
$stmt->execute([$self['id'], $round]);
$self_has_moved = $stmt->fetchColumn() > 0;

// Wie viele Detektive haben schon gezogen?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movements m JOIN players p ON m.player_id = p.id WHERE m.round = ? AND p.role = 'detective'");
$stmt->execute([$round]);
$detectives_moved = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM players WHERE role = 'detective'");
$detectives_total = $stmt->fetchColumn();


// Ausgabe
echo json_encode([
    'self' => $self,
    'tickets' => $tickets,
    'players' => $filtered_players,
    'moves' => $moves,
    'locations' => $locations,
    'connections' => $connections,
    'round' => (int)$round,
    'mr_x_moved' => $mr_x_has_moved,
    'has_moved' => $self_has_moved,
    'detectives_moved' => $detectives_moved,
    'detectives_total' => $detectives_total
]);
