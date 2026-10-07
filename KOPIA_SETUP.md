# Kopia unter Windows

## Installation und geprüfter Stand

Am 07.10.2026 wurde KopiaUI 0.23.1 für den aktuellen Windows-Benutzer installiert. Kopia unterstützt Windows offiziell: [Installationsanleitung](https://kopia.io/docs/installation/).

```powershell
winget install --id Kopia.KopiaUI --exact --source winget --scope user --silent --accept-package-agreements --accept-source-agreements --disable-interactivity
```

Der Installer-Hash wurde von winget geprüft. Die Oberfläche ist über das Startmenü verfügbar. Die mitgelieferte CLI lässt sich ohne PATH-Änderung aufrufen:

```powershell
& "$env:LOCALAPPDATA\Programs\KopiaUI\resources\server\kopia.exe" --version
```

Ein isolierter Test erzeugte ein verschlüsseltes Repository, sicherte eine Testdatei und stellte sie in einen neuen Ordner wieder her. Die SHA256-Prüfsummen stimmten überein. Testdateien, Repository, Konfiguration und Cache wurden anschließend entfernt. Das zufällige Testpasswort wurde nur im Prozess verwendet, weder ausgegeben noch dauerhaft gespeichert. Die Anwendungsdatenbank wurde dabei nicht gesichert oder verändert.

## Echte Sicherung einrichten

Noch erforderlich sind der konkrete Zielpfad beziehungsweise Cloud-Anbieter und der Sicherungsumfang. Ein echtes Repository, automatische Sicherungen und Aufbewahrungsregeln sind noch nicht eingerichtet.

1. Ziel außerhalb des zu sichernden Quellordners festlegen, vorzugsweise auf einem anderen Laufwerk oder System.
2. In KopiaUI ein Repository am vereinbarten Ziel erstellen. Das Verschlüsselungspasswort dort eingeben und sicher verwahren; nicht in Chat, Git oder Dokumentation eintragen.
3. Vor jeder Sicherung einen konsistenten SQLite-Snapshot über PDO `VACUUM INTO` erzeugen. Die laufende `database/database.sqlite` nicht als Ersatz für einen Datenbank-Snapshot kopieren. Die notwendige Vorbereitung und Automatisierung sind noch umzusetzen.
4. Den Snapshot und die vereinbarten Dateien als Quelle konfigurieren. Backup-Ziel und temporäre Dateien ausschließen. Bei vollständiger Projektsicherung auch `.env` und Anwendungsschlüssel geschützt berücksichtigen.
5. Intervall und Aufbewahrung festlegen. Danach eine echte Wiederherstellung in einem separaten Ordner einschließlich Datenbankprüfung durchführen.

Für MariaDB ist später ein vorgeschalteter Dump über `C:/Apps/mariadb/bin/mariadb-dump.exe` erforderlich. Kopia erzeugt selbst keinen Datenbank-Dump. Bei gleichzeitig veränderten Uploads muss die gemeinsame Konsistenz mit der Datenbank gesondert sichergestellt werden.
