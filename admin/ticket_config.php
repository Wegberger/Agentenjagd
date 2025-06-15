<?php
session_start();
if (!isset($_SESSION['admin'])) exit;
require_once('../config.php');

// Ticket-Typen festlegen (falls du später mehr brauchst)
$ticketTypes = ['walk' => 'Zu Fuß', 'bike' => 'Fahrrad', 'special' => 'Spezial'];

// Neue Konfiguration speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($ticketTypes as $type => $label) {
        $amount = intval($_POST[$type] ?? 0);
        
        $stmt = $pdo->prepare("SELECT id FROM ticket_settings WHERE ticket_type = ?");
        $stmt->execute([$type]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE ticket_settings SET amount = ? WHERE ticket_type = ?");
            $stmt->execute([$amount, $type]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO ticket_settings (ticket_type, amount) VALUES (?, ?)");
            $stmt->execute([$type, $amount]);
        }
    }
    $message = "✅ Einstellungen gespeichert.";
}

// Aktuelle Werte holen
$settings = [];
$stmt = $pdo->query("SELECT * FROM ticket_settings");
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['ticket_type']] = $row['amount'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket-Konfiguration</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        input[type=number] { width: 60px; }
    </style>
</head>
<body>
<?php include("menu.php"); ?>

<h1>🌏 Ticket-Konfiguration</h1>

<?php if (isset($message)) echo "<p><strong>$message</strong></p>"; ?>

<form method="post">
    <table>
        <tr><th>Ticket-Typ</th><th>Anzahl pro Spieler</th></tr>
        <?php foreach ($ticketTypes as $key => $label): ?>
        <tr>
            <td><?= $label ?></td>
            <td><input type="number" name="<?= $key ?>" value="<?= $settings[$key] ?? 0 ?>" required></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <button type="submit">💾 Speichern</button>
</form>

</body>
</html>
