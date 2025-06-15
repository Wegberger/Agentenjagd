<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Spieler anlegen – Scotland Yard</title>
  <style>
    body { font-family: sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    td, th { border: 1px solid #ccc; padding: 8px; text-align: left; }
    input, select { font-size: 1em; padding: 4px; width: 95%; }
    button { font-size: 1em; margin-top: 10px; }
  </style>
  <script>
    function addRow() {
      const table = document.getElementById("playerTable");
      const row = table.insertRow(-1);
      row.innerHTML = `
        <td><input name="name[]" required></td>
        <td>
          <select name="role[]">
            <option value="detective">Detektiv</option>
            <option value="mr_x">Mr. X</option>
          </select>
        </td>
        <td><input name="pin_code[]" value="${generatePIN()}" required></td>
      `;
    }

    function generatePIN() {
      return String(Math.floor(1000 + Math.random() * 9000));
    }
  </script>
</head>
<body>
<?php include('menu.php'); ?>
<h1>🎮 Spieler anlegen</h1>

<form method="post" action="save_players.php">
  <table id="playerTable">
    <tr>
      <th>Name</th>
      <th>Rolle</th>
	  <th>Stadt</th>
      <th>PIN</th>
    </tr>
    <tr>
      <td><input name="name[]" required></td>
      <td>
        <select name="role[]">
          <option value="detective">Detektiv</option>
          <option value="mr_x">Mr. X</option>
        </select>
      </td>
	  <select name="city_id[]">
	  <?php foreach ($cities as $c): ?>
		<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
	  <?php endforeach; ?>
	</select>
      <?php $firstPin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); ?>
		<td><input name="pin_code[]" value="<?= $firstPin ?>" required></td>
    </tr>
  </table>
  <button type="button" onclick="addRow()">➕ Weitere Zeile</button><br>
  <button type="submit">💾 Spieler speichern</button>
</form>

<p><a href="index.php">🔙 Zurück zum Adminbereich</a></p>

</body>
</html>
