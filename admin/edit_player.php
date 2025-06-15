<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once('../config.php');

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$pin = trim($_POST['pin_code'] ?? '');

if ($id > 0 && $name !== '' && $pin !== '') {
    $pdo->prepare("UPDATE players SET name = ?, pin_code = ? WHERE id = ?")->execute([$name, $pin, $id]);
}

header("Location: index.php");
exit;
