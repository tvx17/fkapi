# Backup-Prüfung für Windows

Stand: 07.10.2026. Ziel ist eine native Windows-Lösung für Laravel 13, aktuell SQLite und später MariaDB. Kopia wurde ausgewählt; KopiaUI 0.23.1 ist unter Windows installiert und mit temporären Dateien geprüft. Die echte Sicherung wartet auf Zielpfad und Sicherungsumfang. Siehe [Kopia-Einrichtung](KOPIA_SETUP.md).

## Ergebnis und Empfehlung

Spatie Laravel Backup schließt Windows-Server in seinen [offiziellen Anforderungen](https://spatie.be/docs/laravel-backup/v10/requirements) ausdrücklich aus. Passende PHP- und Laravel-Versionen allein ändern diese Einschränkung nicht.

Für dieses Projekt wurde Kopia zur verschlüsselten, versionierten Ablage ausgewählt. Vor der Dateisicherung muss ein konsistenter SQLite-Snapshot beziehungsweise später ein MariaDB-Dump erzeugt werden. Diese Datenbank-Vorbereitung ist noch zu implementieren.

## Vergleich der Optionen

| Option | Windows-Eignung und Nachweis | Integration und Grenzen |
| --- | --- | --- |
| Spatie Laravel Backup | Hersteller schließt Windows aus | Für dieses Windows-Projekt nicht empfohlen |
| Eigener Artisan-Befehl mit PHP/PDO und ZIP | SQLite-Snapshot lokal erfolgreich geprüft; ZIP-Erweiterung vorhanden | Direkte Laravel-Integration; Archivierung, Aufbewahrung, Fehlerbehandlung und Restore-Test müssen implementiert werden |
| Restic | [Projekt nennt Windows ausdrücklich](https://github.com/restic/restic) | Externes CLI-Werkzeug; Datenbank-Snapshot/Dump zuvor separat erzeugen; kein Laravel-Composer-Paket |
| Kopia/KopiaUI | [Windows-CLI und GUI offiziell verfügbar](https://kopia.io/docs/installation/) | Externe Anwendung mit Verschlüsselung, Zeitplanung, Aufbewahrung und Wiederherstellung; Datenbank-Snapshot/Dump ebenfalls separat erzeugen |

Kopia bietet eine Oberfläche für die Backup-Verwaltung außerhalb von Laravel. Restic passt zu einer automatisierten Befehlsfolge. Beide sind unabhängig von Laravels Versionsnummer. Kopia wurde inzwischen lokal unter Windows installiert und durch Sicherung und Wiederherstellung einer Testdatei geprüft; Restic wurde nicht installiert.

Für ein anderes vollständiges Laravel-Backup-Paket konnte bei dieser Recherche keine ausreichend belegte Kombination aus Windows-Unterstützung, Laravel-13-Kompatibilität und passender SQLite-/MariaDB-Sicherung verifiziert werden. Das ist keine Aussage, dass es weltweit keines gibt.

## Lokal geprüfte Voraussetzungen

- PHP 8.5.6, PDO-SQLite mit SQLite 3.51.3 und PHP-ZIP vorhanden.
- Die separate PHP-Klasse `SQLite3` ist hier nicht verfügbar; der empfohlene SQLite-Weg funktioniert über den vorhandenen PDO-Treiber.
- `C:/Apps/mariadb/bin/mariadb-dump.exe` und `mysqldump.exe` vorhanden; derzeit nicht über den normalen PATH auffindbar. Die spätere Integration kann den absoluten Pfad konfigurieren.
- KopiaUI 0.23.1 samt CLI installiert; CLI über den absoluten Pfad aufrufbar, siehe `KOPIA_SETUP.md`. Restic ist nicht installiert.
- Prüfung mit temporärer Testdatenbank: Tabelle und Datensatz angelegt, über PDO `VACUUM INTO` in eine neue Datei gesichert, Sicherung geöffnet und Daten gelesen. Ergebnis: ein erwarteter Datensatz, `PRAGMA integrity_check = ok`.
- Temporäre Sicherung anschließend entfernt. Keine reale Datenbank kopiert oder verändert; keine Zugangsdaten verwendet.
- MariaDB-Dump-Programm vorhanden; ein Dump mit echten Zugangsdaten und eine MariaDB-Wiederherstellung wurden noch nicht geprüft.

## Vorgeschlagener Ablauf

1. **SQLite:** konsistenten Snapshot über PDO und `VACUUM INTO` erzeugen. SQLite dokumentiert dies als Sicherungsmethode für eine laufende Datenbank; die geöffnete Datenbankdatei nicht einfach als gewöhnliche Datei kopieren. [SQLite-Dokumentation](https://www.sqlite.org/lang_vacuum.html)
2. **Später MariaDB:** SQL-Dump über das Windows-Programm `mariadb-dump.exe` erzeugen; passende Transaktions-/Dump-Optionen anhand der Tabellen und Datenbankversion bestimmen. Datenbankpasswörter nicht in Protokollen oder sichtbaren Kommandozeilen hinterlegen. [MariaDB-Dokumentation](https://mariadb.com/docs/server/clients-and-utilities/backup-restore-and-import-clients/mariadb-dump)
3. Snapshot/Dump und benötigte Upload-Dateien zusammen sichern. Temporäre Dateien und Sicherungsverzeichnis von der erneuten Sicherung ausschließen. Code kann aus Git wiederhergestellt werden; Konfiguration und Anwendungsschlüssel benötigen eine gesondert geschützte Sicherung.
4. Ergebnis prüfen und erst nach erfolgreichem Abschluss als gültiges Backup kennzeichnen. Bei Bedarf an Restic/Kopia zur verschlüsselten externen Ablage übergeben.
5. Zeitplanung über die Windows-Aufgabenplanung und explizite Aufbewahrungsregeln ergänzen. Mindestens eine vollständige Wiederherstellung in einer separaten Umgebung testen.

Der Datenbank-Snapshot ist konsistent; eine gleichzeitig veränderliche Dateisammlung wird dadurch nicht automatisch zusammen mit der Datenbank atomar gesichert. Falls Uploads und Datenbank exakt zusammenpassen müssen, Schreibzugriffe während der gemeinsamen Sicherung koordinieren.

## Noch zu entscheiden

- Kopia ist ausgewählt; Datenbank-Vorbereitung und Kopia-Anbindung noch umsetzen.
- Nur Datenbank oder zusätzlich welche Anwendungsdateien?
- Ziel: anderes lokales Laufwerk, NAS oder Cloud?
- Verschlüsselung, Sicherungsintervall und Aufbewahrungsdauer?
- Bei Umsetzung: Backup- und Restore-Tests für SQLite; später zusätzlich MariaDB.
