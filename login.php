<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login – Agentenjagd</title>
  <style>
    body { font-family: sans-serif; text-align: center; padding: 40px; }
    input { font-size: 1.2em; margin: 5px; padding: 5px; width: 200px; }
  </style>
</head>
<body>

<h2>🔑 Agentenjagd – Login</h2>

<form method="post" action="do_login.php">
  <input type="text" name="name" placeholder="Dein Name" required><br>
  <input type="text" name="pin" placeholder="PIN-Code" required><br>
  <button type="submit">Anmelden</button>
</form>

<?php if (isset($_GET['error'])): ?>
  <p style="color:red">❌ Login fehlgeschlagen</p>
<?php endif; ?>

<script>
const urlParams = new URLSearchParams(window.location.search);
const name = urlParams.get('name');
const pin = urlParams.get('pin');

if (name && pin) {
    document.querySelector('[name="name"]').value = name;
    document.querySelector('[name="pin"]').value = pin;

    // Formular automatisch abschicken
    document.forms[0].submit();
}
</script>

</body>
</html>
