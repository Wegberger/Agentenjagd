-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 15. Jun 2025 um 12:57
-- Server-Version: 10.6.22-MariaDB-0ubuntu0.22.04.1
-- PHP-Version: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Datenbank: `c0scotyard`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `center_lat` double DEFAULT NULL,
  `center_lng` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `connections`
--

CREATE TABLE `connections` (
  `id` int(11) NOT NULL,
  `from_punkt` int(11) NOT NULL,
  `to_punkt` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `allowed_ticket` enum('walk','bike','special','black') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `game_status`
--

CREATE TABLE `game_status` (
  `id` int(11) NOT NULL,
  `current_round` int(11) DEFAULT 1,
  `reveal_rounds` text DEFAULT NULL,
  `gps_tolerance` float DEFAULT NULL,
  `last_change` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `punkt_nr` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `movements`
--

CREATE TABLE `movements` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `ticket_type` enum('walk','bike','special','black') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `round` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `role` enum('detective','mr_x') NOT NULL,
  `last_lat` double DEFAULT NULL,
  `last_lng` double DEFAULT NULL,
  `last_update` datetime DEFAULT current_timestamp(),
  `visible` tinyint(1) DEFAULT 0,
  `pin_code` varchar(10) DEFAULT NULL,
  `constrain_location` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `player_tickets`
--

CREATE TABLE `player_tickets` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `ticket_type` enum('walk','bike','special') NOT NULL,
  `remaining` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ticket_settings`
--

CREATE TABLE `ticket_settings` (
  `id` int(11) NOT NULL,
  `ticket_type` enum('walk','bike','special') NOT NULL,
  `amount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_connection` (`from_punkt`,`to_punkt`,`city_id`,`allowed_ticket`),
  ADD KEY `city_id` (`city_id`,`from_punkt`),
  ADD KEY `city_id_2` (`city_id`,`to_punkt`);

--
-- Indizes für die Tabelle `game_status`
--
ALTER TABLE `game_status`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `city_id` (`city_id`,`punkt_nr`),
  ADD UNIQUE KEY `city_punkt_unique` (`city_id`,`punkt_nr`);

--
-- Indizes für die Tabelle `movements`
--
ALTER TABLE `movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `fk_movements_player` (`player_id`);

--
-- Indizes für die Tabelle `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `player_tickets`
--
ALTER TABLE `player_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indizes für die Tabelle `ticket_settings`
--
ALTER TABLE `ticket_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `movements`
--
ALTER TABLE `movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `player_tickets`
--
ALTER TABLE `player_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `ticket_settings`
--
ALTER TABLE `ticket_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `connections`
--
ALTER TABLE `connections`
  ADD CONSTRAINT `connections_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `connections_ibfk_2` FOREIGN KEY (`city_id`,`from_punkt`) REFERENCES `locations` (`city_id`, `punkt_nr`) ON DELETE CASCADE,
  ADD CONSTRAINT `connections_ibfk_3` FOREIGN KEY (`city_id`,`to_punkt`) REFERENCES `locations` (`city_id`, `punkt_nr`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `fk_locations_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `movements`
--
ALTER TABLE `movements`
  ADD CONSTRAINT `fk_movements_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movements_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `movements_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints der Tabelle `player_tickets`
--
ALTER TABLE `player_tickets`
  ADD CONSTRAINT `player_tickets_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;
COMMIT;
