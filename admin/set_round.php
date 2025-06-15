<?php
require_once('../config.php');

$round = intval($_POST['round']);
$pdo->prepare("UPDATE game_status SET current_round = ?, last_change = NOW() WHERE id = 1")->execute([$round]);

// Optional: Mr. X automatisch sichtbar machen
$stmt = $pdo->prepare("SELECT reveal_rounds FROM game_status WHERE id = 1");
$stmt->execute();
$rounds = explode(',', $stmt->fetchColumn());

$pdo->prepare("UPDATE players SET visible = (role = 'mr_x' AND ? IN (" . implode(',', array_fill(0, count($rounds), '?')) . "))")->execute(array_merge([$round], $rounds));

header("Location: index.php");
