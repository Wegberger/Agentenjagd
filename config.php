<?php
// config.php (anonymisierte Version für GitHub)

$host = 'localhost';
$db   = 'DB_NAME';
$user = 'DB_USER';
$pass = 'DB_PASS';
$charset = 'utf8mb4';

// Google Maps API Key (Beispielwert – bitte echten Key nicht veröffentlichen)
$googleMapsApiKey = 'YOUR_GOOGLE_MAPS_API_KEY';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}
?>
