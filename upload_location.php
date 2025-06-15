<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['player_id'])) {
    http_response_code(403);
    echo "Nicht eingeloggt.";
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['ticket_type'], $data['lat'], $data['lng'])) {
    http_response_code(400);
    echo "Ungültige Daten";
    exit;
}

$player_id = $_SESSION['player_id'];
$ticket = $data['ticket_type'];
$lat = floatval($data['lat']);
$lng = floatval($data['lng']);

// Position aktualisieren
$stmt = $pdo->prepare("UPDATE players SET last_lat = ?, last_lng = ?, last_update = NOW() WHERE id = ?");
$stmt->execute([$lat, $lng, $player_id]);

// Bewegung speichern
$stmt = $pdo->prepare("INSERT INTO movements (player_id, ticket_type, lat, lng) VALUES (?, ?, ?, ?)");
$stmt->execute([$player_id, $ticket, $lat, $lng]);

echo "📍 Standort gespeichert!";

