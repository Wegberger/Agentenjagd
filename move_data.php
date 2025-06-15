<?php
session_start();
if (!isset($_SESSION['player_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Nicht eingeloggt"]);
    exit;
}

require_once('config.php');
$player_id = $_SESSION['player_id'];

// Eigene Daten abrufen
$stmt = $pdo->prepare("SELECT id, name, role, last_lat, last_lng, visible FROM players WHERE id = ?");
$stmt->execute([$player_id]);
$self = $stmt->fetch();

// Runde abrufen
$current_round = $pdo->query("SELECT current_round FROM game_status WHERE id = 1")->fetchColumn();

// Spielzüge des Spielers zählen
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movements WHERE player_id = ? AND round = ?");
$stmt->execute([$player_id, $current_round]);
$alreadyMoved = $stmt->fetchColumn() > 0;

if (!$self || $alreadyMoved) {
    echo json_encode([
        'self' => $self,
        'tickets' => [],
        'players' => [],
        'moves' => []
    ]);
    exit;
}

// Tickets abrufen
$stmt = $pdo->prepare("SELECT ticket_type, remaining FROM player_tickets WHERE player_id = ?");
$stmt->execute([$player_id]);
$tickets = [];
foreach ($stmt->fetchAll() as $row) {
    $tickets[$row['ticket_type']] = (int)$row['remaining'];
}

// Sichtbare Spieler
$stmt = $pdo->query("SELECT id, name, role, last_lat, last_lng, visible FROM players WHERE last_lat IS NOT NULL AND last_lng IS NOT NULL");
$players = [];
foreach ($stmt->fetchAll() as $p) {
    if ($p['id'] == $player_id || $p['visible']) {
        $players[] = $p;
    }
}

// Punktnummer ermitteln
$stmt = $pdo->prepare("SELECT id, punkt_nr FROM locations WHERE ABS(lat - ?) < 0.0005 AND ABS(lng - ?) < 0.0005 LIMIT 1");
$stmt->execute([$self['last_lat'], $self['last_lng']]);
$currentLocation = $stmt->fetch();

$connections = [];
if ($currentLocation) {
    $stmt = $pdo->prepare("SELECT to_punkt, allowed_ticket FROM connections WHERE from_punkt = ?");
    $stmt->execute([$currentLocation['punkt_nr']]);
    foreach ($stmt->fetchAll() as $conn) {
        if (!isset($tickets[$conn['allowed_ticket']]) || $tickets[$conn['allowed_ticket']] <= 0) continue;

        $target = $pdo->prepare("SELECT id, punkt_nr, lat, lng FROM locations WHERE punkt_nr = ? LIMIT 1");
        $target->execute([$conn['to_punkt']]);
        $targetData = $target->fetch();

        if ($targetData) {
            $connections[] = [
                'to_punkt' => $targetData['punkt_nr'],
                'location_id' => $targetData['id'],
                'lat' => $targetData['lat'],
                'lng' => $targetData['lng'],
                'ticket' => $conn['allowed_ticket']
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'self' => $self ?? [],
    'tickets' => $tickets ?? [],
    'players' => $players ?? [],
    'moves' => $connections ?? []
]);
