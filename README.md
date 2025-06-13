# 🕵️ Agentenjagd – Das Real-Life Scotland Yard Webspiel

**Agentenjagd** ist eine Webanwendung für ein analoges Detektivspiel im Stil von *Scotland Yard*, bei dem echte Spieler sich im Gelände bewegen und über eine Webkarte verfolgt werden.

## 🔍 Features

- Echtzeit-Spielerkarte mit Google Maps
- Versteckter Agent (Mr. X) sichtbar nur in bestimmten Runden
- Bewegungen über Knotenpunkte mit Ticketsystem
- Verwaltung über Admin-Oberfläche
- GPS-Ortung (optional)
- Unterstützt Mobilgeräte und Tablets

## 💻 Technik

- PHP (Backend)
- MySQL (Datenbank)
- JavaScript mit Leaflet / Google Maps (Frontend)

## 📦 Struktur

```text
/
├── index.php              # Startseite / Login
├── map.php                # Spielerkarte
├── move.php               # Bewegungssystem
├── admin/                 # Spielleitung & Steuerung
├── js/                    # JavaScript-Funktionen
├── css/                   # Stylesheets
├── db/schema.sql          # Beispielhafte Datenbankstruktur
├── inc/db-connect.php     # Datenbankverbindung (Dummywerte)
├── LICENSE
└── README.md
