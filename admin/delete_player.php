<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once('../config.php');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id > 0) {
    try {
        // zuerst Bewegungen löschen (falls vorhanden)
        $stmt = $pdo->prepare("DELETE FROM movements WHERE player_id = ?");
        $stmt->execute([$id]);

        // dann Spieler selbst löschen
        $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
        $stmt->execute([$id]);

    } catch (PDOException $e) {
        error_log("Fehler beim Löschen: " . $e->getMessage());
        // Optional: HTML-Fehlermeldung anzeigen
        die("❌ Fehler beim Löschen des Spielers.");
    }
}

header("Location: index.php");
exit;
