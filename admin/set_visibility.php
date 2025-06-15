<?php
require_once('../config.php');

$id = $_POST['id'];
$visible = $_POST['visible'] ? 1 : 0;

$pdo->prepare("UPDATE players SET visible = ? WHERE id = ?")->execute([$visible, $id]);
header("Location: index.php");
