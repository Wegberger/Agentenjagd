<?php
session_start();
if (!isset($_SESSION['admin'])) exit;
require_once('../config.php');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $stmt = $pdo->prepare("UPDATE locations SET punkt_nr = ?, name = ?, lat = ?, lng = ? WHERE id = ?");
    $stmt->execute([$data['punkt_nr'], $data['name'], $data['lat'], $data['lng'], $data['id']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO locations (punkt_nr, name, lat, lng, city_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['punkt_nr'], $data['name'], $data['lat'], $data['lng'], $data['city_id']]);
}
