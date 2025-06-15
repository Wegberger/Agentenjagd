<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once('../config.php');

$names = $_POST['name'] ?? [];
$roles = $_POST['role'] ?? [];
$pins = $_POST['pin_code'] ?? [];

for ($i = 0; $i < count($names); $i++) {
    $name = trim($names[$i]);
    $role = $roles[$i] === 'mr_x' ? 'mr_x' : 'detective';
    $pin = trim($pins[$i]);

    if ($name !== '' && $pin !== '') {
        $stmt = $pdo->prepare("INSERT INTO players (name, role, pin_code) VALUES (?, ?, ?)");
        $stmt->execute([$name, $role, $pin]);
    }
}

header("Location: index.php");
exit;
