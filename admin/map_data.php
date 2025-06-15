<?php
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Nicht eingeloggt"]);
    exit;
}

require_once('../config.php');

// Spieler mit letzter Position abrufen
$stmt = $pdo->query("
    SELECT 
        p.id, p.name, p.role, p.visible, p.last_lat, p.last_lng,
        (
            SELECT m2.timestamp 
            FROM movements m2 
            WHERE m2.player_id = p.id 
            ORDER BY m2.timestamp DESC 
            LIMIT 1
        ) AS last_time,
        (
            SELECT JSON_OBJECT('lat', m1.lat, 'lng', m1.lng)
            FROM movements m1
            WHERE m1.player_id = p.id AND m1.lat IS NOT NULL AND m1.lng IS NOT NULL
            ORDER BY m1.timestamp DESC 
            LIMIT 1 OFFSET 1
        ) AS previous_position
    FROM players p
    WHERE p.last_lat IS NOT NULL AND p.last_lng IS NOT NULL
");

$players = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($players);
