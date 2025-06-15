<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once("../config.php");

// 1. Alte Bewegungen löschen
$pdo->exec("DELETE FROM movements");

// 2. Spielrunde zurücksetzen
$pdo->exec("UPDATE game_status SET current_round = 1, last_change = NOW() WHERE id = 1");

// 3. Spieler zufällig auf Startpunkte setzen
$playerCount = $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn();
$stmt = $pdo->prepare("SELECT lat, lng FROM locations ORDER BY RAND() LIMIT ?");
$stmt->execute([$playerCount]);
$locations = $stmt->fetchAll();

$players = $pdo->query("SELECT id FROM players")->fetchAll();


// Ticket-Einstellungen laden
$ticketSettingsStmt = $pdo->query("SELECT ticket_type, amount FROM ticket_settings");
$ticketSettings = [];
foreach ($ticketSettingsStmt as $row) {
    $ticketSettings[$row['ticket_type']] = (int)$row['amount'];
}

foreach ($players as $index => $player) {
    $lat = $locations[$index]['lat'];
    $lng = $locations[$index]['lng'];
    $player_id = $player['id'];

    // Position setzen
    $stmt = $pdo->prepare("UPDATE players SET last_lat = ?, last_lng = ? WHERE id = ?");
    $stmt->execute([$lat, $lng, $player_id]);

    // Sichtbarkeit zurücksetzen
    $pdo->prepare("UPDATE players SET visible = 0 WHERE id = ?")->execute([$player_id]);

    // Tickets initial einfügen oder aktualisieren
    foreach ($ticketSettings as $type => $amount) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM player_tickets WHERE player_id = ? AND ticket_type = ?");
        $stmt->execute([$player_id, $type]);
        if ($stmt->fetchColumn() == 0) {
            $insert = $pdo->prepare("INSERT INTO player_tickets (player_id, ticket_type, remaining) VALUES (?, ?, ?)");
            $insert->execute([$player_id, $type, $amount]);
        } else {
            $update = $pdo->prepare("UPDATE player_tickets SET remaining = ? WHERE player_id = ? AND ticket_type = ?");
            $update->execute([$amount, $player_id, $type]);
        }
    }
}


header("Location: index.php?started=1");
exit;
?>
