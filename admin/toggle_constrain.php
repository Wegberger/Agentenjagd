<?php
require_once('../config.php');
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit("Nicht erlaubt");
}

$player_id = (int)($_POST['player_id'] ?? 0);
$constrain = isset($_POST['constrain']) ? 1 : 0;

if ($player_id) {
    $stmt = $pdo->prepare("UPDATE players SET constrain_location = ? WHERE id = ?");
    $stmt->execute([$constrain, $player_id]);
}
header("Location: index.php");
exit;
