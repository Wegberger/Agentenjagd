<?php
require_once('../config.php');

$id = $_POST['id'];
$role = $_POST['role'];

$pdo->prepare("UPDATE players SET role = ? WHERE id = ?")->execute([$role, $id]);
header("Location: index.php");
