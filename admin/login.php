<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['user'] ?? '';
    $password = $_POST['pass'] ?? '';

    // ✏️ Hier echte Zugangsdaten eintragen
    if ($username === 'admin' && $password === 'password') {
        $_SESSION['admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin-Login</title>
</head>
<body>
  <h2>🔐 Admin-Login</h2>
  <form method="post">
    <input name="user" placeholder="Benutzername" required><br>
    <input type="password" name="pass" placeholder="Passwort" required><br>
    <button type="submit">Einloggen</button>
  </form>
  <?php if (!empty($error)) echo "<p style='color:red'>❌ Login fehlgeschlagen</p>"; ?>
</body>
</html>
