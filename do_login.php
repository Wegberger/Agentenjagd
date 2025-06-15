<?php
session_start();
require_once('config.php');

$name = trim($_POST['name']);
$pin = trim($_POST['pin']);

$stmt = $pdo->prepare("SELECT id FROM players WHERE name = ? AND pin_code = ?");
$stmt->execute([$name, $pin]);

if ($player = $stmt->fetch()) {
    $_SESSION['player_id'] = $player['id'];
    header("Location: map.php");
    exit;
} else {
    header("Location: login.php?error=1");
    exit;
}
