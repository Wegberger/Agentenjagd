<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once('../config.php');

// Spieler abrufen
$stmt = $pdo->query("SELECT * FROM players ORDER BY name");
$players = $stmt->fetchAll();

// Spielstatus abrufen oder initialisieren
$statusStmt = $pdo->query("SELECT * FROM game_status WHERE id = 1");
$status = $statusStmt->fetch();

if (!$status) {
    $pdo->exec("INSERT INTO game_status (id, current_round, reveal_rounds) VALUES (1, 1, '3,8,13')");
    $status = ['current_round' => 1, 'reveal_rounds' => '3,8,13'];
}

$revealRounds = explode(',', $status['reveal_rounds']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin – Scotland Yard</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        td, th { padding: 8px; border: 1px solid #ccc; text-align: left; }
        form.inline { display: inline; margin: 0; }
        input[type=number], input[type=text] { width: 60px; }
        h1, h2 { margin-top: 20px; }
        .logout { position: absolute; top: 10px; right: 20px; }
    </style>
</head>
<body>

<?php include('menu.php'); ?>

<h1>🎛️ Adminbereich – Scotland Yard</h1>

<h2>🧍 Spielerübersicht</h2>
<table>
    <tr>
        <th>Name</th>
        <th>Rolle</th>
        <th>Letzte Position</th>
        <th>Sichtbar?</th>
        <th>Standort</th>
        <th>Aktionen</th>
    </tr>
    <?php foreach ($players as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= $p['role'] ?></td>
            <td><?= $p['last_lat'] ?> / <?= $p['last_lng'] ?></td>
            <td><?= $p['visible'] ? '✅' : '❌' ?>
        
        <!-- Sichtbarkeit -->
            <form class="inline" action="set_visibility.php" method="post">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="visible" value="<?= $p['visible'] ? 0 : 1 ?>">
                <button type="submit"><?= $p['visible'] ? 'Verstecken' : 'Zeigen' ?></button>
            </form>
            </td>
            <td>
            <!-- Standortprüfung -->
            <form class="inline" action="toggle_constrain.php" method="post">
                <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                <label style="font-size: 0.9em">
                    📍
                    <input type="checkbox" name="constrain" value="1" onchange="this.form.submit()" <?= $p['constrain_location'] ? 'checked' : '' ?>>
                    Standort prüfen
                </label>
            </form>
            </td>
            <td>
			<!-- Rollenwahl -->
            <form class="inline" action="set_role.php" method="post">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <select name="role" onchange="this.form.submit()">
                    <option value="detective" <?= $p['role'] == 'detective' ? 'selected' : '' ?>>Detektiv</option>
                    <option value="mr_x" <?= $p['role'] == 'mr_x' ? 'selected' : '' ?>>Mr. X</option>
                </select>
            </form>

            <!-- Name/PIN ändern -->
<form class="inline" action="edit_player.php" method="post">
    <input type="hidden" name="id" value="<?= $p['id'] ?>">
    <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" placeholder="Name" size="10">
    <input type="text" name="pin_code" value="<?= htmlspecialchars($p['pin_code']) ?>" placeholder="PIN" size="5">
    <button type="submit">✏️</button>
</form>

<!-- Login-Link kopieren -->
<button onclick="copyLoginLink('<?= htmlspecialchars($p['name']) ?>', '<?= htmlspecialchars($p['pin_code']) ?>')">🔗</button>

<!-- Spieler löschen -->
<form class="inline" action="delete_player.php" method="post" onsubmit="return confirm('Spieler wirklich löschen?');">
    <input type="hidden" name="id" value="<?= $p['id'] ?>">
    <button type="submit">🗑️</button>
</form>

        </td>

        </tr>
    <?php endforeach; ?>
</table>

<h2>📆 Spielrunde: <?= $status['current_round'] ?></h2>
<form action="set_round.php" method="post">
    <label>Neue Runde: <input type="number" name="round" value="<?= $status['current_round'] ?>" required></label>
    <button type="submit">Ändern</button>
</form>
<form method="post" action="game_start.php" onsubmit="return confirm('Spiel wirklich starten? Vorherige Positionen & Tickets werden überschrieben.')">
    <button type="submit">🎮 Spiel starten</button>
</form>

<p>🔍 Mr. X ist sichtbar in Runde(n): <?= htmlspecialchars($status['reveal_rounds']) ?></p>

<script>
function copyLoginLink(name, pin) {
    const url = new URL('https://agent.muehlen.name/login.php');
    url.searchParams.set('name', name);
    url.searchParams.set('pin', pin);
    navigator.clipboard.writeText(url.href).then(() => {
        alert("✅ Login-Link kopiert:\n" + url.href);
    }).catch(err => {
        alert("❌ Fehler beim Kopieren: " + err);
    });
}
</script>


</body>
</html>
