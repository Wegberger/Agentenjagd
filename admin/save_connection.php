<?php
session_start();
if (!isset($_SESSION['admin'])) exit;
require_once('../config.php');

$data = json_decode(file_get_contents("php://input"), true);

$from = intval($data['from_punkt']);
$to = intval($data['to_punkt']);
$ticket = $data['ticket'];
$city_id = intval($data['city_id']);

if (in_array($ticket, ['walk', 'bike', 'special', 'black']) && $from > 0 && $to > 0) {
    $stmt = $pdo->prepare("INSERT INTO connections (from_punkt, to_punkt, city_id, allowed_ticket) VALUES (?, ?, ?, ?)");
    $stmt->execute([$from, $to, $city_id, $ticket]);
    echo "✅ Verbindung gespeichert";
} else {
    http_response_code(400);
    echo "❌ Ungültige Eingabe";
}
