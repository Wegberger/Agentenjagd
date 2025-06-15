<?php
session_start();
if (!isset($_SESSION['admin'])) exit;
require_once('../config.php');

$name = $_POST['name'];
$lat = floatval($_POST['lat']);
$lng = floatval($_POST['lng']);

$stmt = $pdo->prepare("INSERT INTO cities (name, center_lat, center_lng) VALUES (?, ?, ?)");
$stmt->execute([$name, $lat, $lng]);

header("Location: locations.php?city=" . $pdo->lastInsertId());
exit;
