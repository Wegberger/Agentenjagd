<?php if (!isset($_SESSION)) session_start(); ?>
<nav style="background:#f4f4f4; padding:10px; border-bottom:1px solid #ccc; margin-bottom:20px;">
    <strong>🔧 Admin-Menü:</strong>
    <a href="index.php" style="margin-right:15px;">🏠 Spielerübersicht</a>
    <a href="setup_players.php" style="margin-right:15px;">👥 Spieler anlegen</a>
	<a href="ticket_config.php" style="margin-right:15px;"> Tickets bearbeiten</a>
    <a href="locations.php" style="margin-right:15px;">🗺️ Knotenpunkte / Städte</a>
    <a href="map.php" style="margin-right:15px;">📍 Live-Karte</a>
    <a href="logout.php" style="float:right;">🚪 Logout</a>
</nav>
