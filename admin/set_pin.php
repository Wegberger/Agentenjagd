<?php
require_once('../config.php');
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit("Nicht erlaubt.");
}

$id = $_POST['id'];
$pin = $_POST['pin_code'];

$pdo->prepare("UPDATE players SET pin_code = ? WHERE id = ?")->execute([$pin, $id]);
header("Location: index.php");
