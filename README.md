# LaravelAPI

Eine minimale API-Basis mit Laravel 13 und PHP ab 8.3.

Arbeitsschritte, Entscheidungen, Prüfergebnisse und offene Aufgaben werden in [PROJEKTDOKUMENTATION.md](PROJEKTDOKUMENTATION.md) fortlaufend festgehalten.

Windows-Eignung und Alternativen für Sicherungen: [BACKUP_PRUEFUNG.md](BACKUP_PRUEFUNG.md).

## Codex-Sitzung wieder aufnehmen

```powershell
.\Resume-Sitzung.ps1
```

Das Skript nimmt diese Sitzung anhand ihrer gespeicherten ID im Projektverzeichnis wieder auf. Codex CLI muss im PATH verfügbar sein; der Verlauf muss im gleichen lokalen Codex-Profil erhalten sein. Mit `-Auswahl` stattdessen eine gespeicherte Projektsitzung auswählen, mit `-SessionId ID` eine andere Sitzung öffnen. `-WhatIf` zeigt den vorgesehenen Aufruf ohne Codex zu starten. Das Skript startet keine Laravel-Dienste.

Grundlage: [offizielle Codex-Befehlsreferenz](https://learn.chatgpt.com/docs/developer-commands?surface=cli).

## Lokal starten

Abhängigkeiten und lokaler Anwendungsschlüssel sind bereits eingerichtet.

```powershell
php artisan serve
```

Erreichbar unter `http://127.0.0.1:8000`:

- `GET /`: Anwendungsname und Status im gemeinsamen API-Antwortformat.
- `GET /api/status`: Status im gemeinsamen API-Antwortformat.
- `GET /up`: Laravel-Healthcheck.

Unbekannte API-Routen liefern HTTP 404 als JSON, auch ohne Accept-Header. Die Status-Endpunkte benötigen keine Datenbank.

## Neuer Checkout

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite -ErrorAction SilentlyContinue
php artisan migrate
```

Aktuell verwenden wir SQLite (`DB_CONNECTION=sqlite`); MariaDB ist später vorgesehen. Die lokale Datenbank liegt in `database/database.sqlite` und wird nicht eingecheckt. Die Runner UI verwendet eine Browser-Anmeldung. Ein Frontend-Build ist für die API nicht erforderlich.

## Rollen und Berechtigungen

Spatie Laravel Permission ist im User-Modell integriert. Konfiguration: `config/permission.php`. Die Migration erstellt Rollen, Berechtigungen und deren Zuordnungstabellen; fachliche Rollen und Rechte werden noch nicht angelegt.

Beispiel für die spätere Verwendung in PHP:

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$permission = Permission::findOrCreate('users.view', 'web');
$role = Role::findOrCreate('reader', 'web');
$role->givePermissionTo($permission);
$user->assignRole($role);
$user->can('users.view');
```

Der aktuell konfigurierte Guard heißt `web`. Verwende bei Rollen und Berechtigungen denselben Guard wie bei den Benutzern. Verfügbar sind die Middleware-Aliase `permission`, `role` und `role_or_permission`; für einzelne Rechte ist auch Laravels `can`-Middleware nutzbar.

Bei Änderungen über die Paketmethoden wird der Berechtigungscache automatisch aktualisiert. Bei manuellen Datenbankänderungen: `php artisan permission:cache-reset`.

Für den späteren Wechsel auf MariaDB `DB_CONNECTION=mariadb` sowie `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` und `DB_PASSWORD` lokal konfigurieren, dann `php artisan config:clear` und `php artisan migrate` ausführen. Bestehende SQLite-Daten werden dabei nicht übertragen.

## Artisan Runner UI

`codesquirrel/artisan-runner-ui` ist installiert. Die Konfiguration liegt in `config/artisan-runner-ui.php`. Lokal ist die UI aktiviert. Neue Checkouts starten mit `ARTISAN_RUNNER_UI_ENABLED=false`; zum Aktivieren den Wert in `.env` auf `true` setzen und `php artisan config:clear` ausführen.

Die Oberfläche liegt unter `/artisan-runner-ui`. Anmeldung: `/login`; Konto und Abmeldung: `/runner`. Die Middleware `web`, `auth:web` und `can:run-artisan-runner-ui` schützt sowohl die Oberfläche als auch die Befehlsausführung. Die Paketkonfiguration erlaubt ausschließlich die Umgebungen `local`, `qa`, `staging` und `uat`.

Die Listen `allowed` und `denied` steuern verfügbare Befehle. Die Oberfläche benötigt keinen Frontend-Build und keine zusätzliche Migration. Die veröffentlichte Blade-Ansicht enthält einen Link zum Konto; bei Paketupdates Änderungen an der Originalansicht prüfen.

## Zugangsbenutzer anlegen

```powershell
php artisan runner:create-user admin@example.com --name="Administrator"
```

Der Befehl fragt das Passwort verdeckt inklusive Bestätigung ab (mindestens 12 Zeichen), speichert es gehasht und vergibt ausschließlich die Berechtigung `run-artisan-runner-ui` für den Guard `web`. Bereits vorhandene Benutzer werden nicht geändert. Es gibt keine öffentliche Registrierung und keine automatisch erzeugten Standardzugänge. Der Befehl ist in der Runner UI gesperrt.

Bestehenden Benutzern kann die Berechtigung über Spatie vergeben oder entzogen werden:

```php
$permission = Permission::findOrCreate('run-artisan-runner-ui', 'web');
$user->givePermissionTo($permission);
$user->revokePermissionTo($permission);
```

Die Anmeldung verwendet Sessions, CSRF-Schutz und begrenzte Anmeldeversuche. Beim Anmelden wird die Session-ID erneuert; beim Abmelden werden Session und CSRF-Token erneuert. Eine API-Token-Anmeldung ist noch nicht eingerichtet.

## Laravel Telescope

Telescope ist als Entwicklungsabhängigkeit installiert und wird ausschließlich in `APP_ENV=local` registriert. Lokal ist das Dashboard unter `http://127.0.0.1:8000/telescope` ohne Anmeldung erreichbar. In anderen Umgebungen werden keine Telescope-Routen registriert; `composer install --no-dev` ist weiterhin möglich.

Konfiguration: `config/telescope.php`; Filter und Maskierung: `app/Providers/TelescopeServiceProvider.php`. `TELESCOPE_ENABLED=false` deaktiviert lokal Aufzeichnung und Dashboard. Nach einer Änderung gegebenenfalls `php artisan config:clear` ausführen.

Requests, Fehler, Datenbankabfragen und weitere Ereignisse werden in der konfigurierten Datenbank gespeichert, aktuell SQLite. Request-Passwörter, Cookies, Authorization- und CSRF-Werte werden auch lokal maskiert. Die mitgelieferten Dashboard-Assets benötigen keinen eigenen Frontend-Build.

Alte Einträge bei Bedarf entfernen:

```powershell
php artisan telescope:prune --hours=48
```

Der Befehl entfernt Aufzeichnungen, die älter als 48 Stunden sind. Eine automatische Bereinigung ist noch nicht eingerichtet. Bei einem neuen Checkout legt `php artisan migrate` auch die Telescope-Tabellen an.

## Filament Admin-Panel

Filament 5 ist unter `http://127.0.0.1:8000/admin` erreichbar, Anmeldung unter `/admin/login`. Panel-Konfiguration: `app/Providers/Filament/AdminPanelProvider.php`. Das Panel nutzt den vorhandenen `web`-Guard und benötigt in jeder Umgebung die Spatie-Berechtigung `access-admin-panel`.

Ein Konto lokal interaktiv anlegen und anschließend gezielt freischalten:

```powershell
php artisan make:filament-user
php artisan admin:grant-access admin@example.com
```

Alternativ nur den zweiten Befehl für einen bestehenden Benutzer ausführen. Kein Standardkonto wurde angelegt. Runner-Zugriff und Admin-Zugriff sind getrennte Berechtigungen. Die Befehle zur Kontoanlage und Admin-Freischaltung sind in der Runner UI gesperrt. Zugriff über Spaties `revokePermissionTo('access-admin-panel')` entziehen.

Das Panel enthält zunächst nur das Dashboard; keine fachlichen Ressourcen oder öffentliche Registrierung. Bei neuen Checkouts werden die mitgelieferten Assets durch den Composer-Hook `php artisan filament:upgrade` veröffentlicht. Für das Standardpanel ist kein eigener Node-/Tailwind-Build nötig. Nach Paketupdates bei Bedarf denselben Befehl ausführen. [Offizielle Installation](https://filamentphp.com/docs/5.x/introduction/installation).

## Socialite

Laravel Socialite ist als reguläre Abhängigkeit installiert und wird automatisch von Laravel registriert. Einrichtung nach der [offiziellen Dokumentation](https://laravel.com/framework/docs/socialite).

Ein OAuth-Anbieter und Login-/Callback-Routen sind noch nicht eingerichtet. Nach Auswahl des Anbieters dessen `client_id`, `client_secret` und `redirect` in `config/services.php` mit Werten aus `.env` konfigurieren; Platzhalter in `.env.example` dokumentieren. Die Callback-URL muss mit der beim Anbieter registrierten URL übereinstimmen. Zugangsdaten ausschließlich lokal hinterlegen.

Vor Umsetzung festlegen, ob Socialite die Browser-Anmeldung oder die API-Anmeldung ergänzen soll und wie externe Konten mit Benutzern verknüpft werden. Socialite allein stellt keine API-Tokens aus und vergibt keine Runner-Berechtigung.

## Spatie Media Library

Die kostenlose Spatie Media Library ist als Grundlage installiert. Konfiguration: `config/media-library.php`; die veröffentlichte Migration erstellt die Tabelle `media`. Bei einem neuen Checkout `php artisan migrate` ausführen.

Die Standardablage ist die Disk `public` unter `storage/app/public`; `MEDIA_DISK` kann eine andere Disk aus `config/filesystems.php` wählen. Die Dateigrößengrenze beträgt standardmäßig 10 MB. Für öffentliche URLs erst bei Bedarf `php artisan storage:link` ausführen; unter Windows können dafür entsprechende Rechte erforderlich sein.

Noch kein Anwendungsmodell und keine Upload-Route angebunden. Das gewünschte Modell muss `Spatie\MediaLibrary\HasMedia` implementieren und `Spatie\MediaLibrary\InteractsWithMedia` verwenden. Vor API-Uploads Dateitypen, Größen, Berechtigungen und öffentliche oder private Ablage festlegen.

GD ist als Bildtreiber konfiguriert und lokal vorhanden. Konvertierungen laufen standardmäßig über die Datenbank-Queue (`php artisan queue:work`). PDF-/SVG-Vorschaubilder benötigen zusätzliche Werkzeuge, Video-Vorschaubilder FFmpeg; deren Windows-Installation und Pfade erst vor Nutzung prüfen. Es wurden keine Konvertierungen eingerichtet. [Offizielle Anforderungen](https://spatie.be/docs/laravel-medialibrary/v11/requirements).

Kopia muss später sowohl die zugehörigen Dateien als auch die Datenbank sichern; die Tabelle `media` allein enthält nicht die Dateien.

## Backups mit Kopia

KopiaUI ist lokal unter Windows installiert; Sicherung und Wiederherstellung mit temporären Dateien wurden geprüft. Ziel, Umfang und automatische Datenbank-Vorbereitung stehen noch aus. Details: [Kopia-Einrichtung](KOPIA_SETUP.md) und [Windows-Backup-Prüfung](BACKUP_PRUEFUNG.md).

## Prüfung

```powershell
php artisan test
php vendor/bin/pint --test
php artisan route:list --path=api
```

Tests verwenden SQLite im Arbeitsspeicher. API-Tests liegen in `tests/Feature/ApiTest.php`, Anwendungslogik in `app/`, API-Routen in `routes/api.php`.

## API-Antwortformat

Eigene API-Actions verwenden den Trait `App\Traits\ApiResponse`. Jede Antwort enthält `code` (fünfstelliger String), `status` (Boolean), `data` und `responseStatus` (zusätzlich zum tatsächlichen HTTP-Status).

```php
return $this->success(UserResponseCode::LoginSuccessful, $data);
return $this->error(UserResponseCode::CredentialsError, null, 401);
```

`success()` setzt `status=true` und standardmäßig HTTP 200; `error()` setzt `status=false` und standardmäßig HTTP 400. Beide akzeptieren einen String-backed Enum-Code, optionale Daten und den HTTP-Status. Daten dürfen auch `null` sein.

Enums liegen in `app/Enums/Api/`. Die ersten zwei Ziffern identifizieren den Bereich, die letzten drei das Ereignis. `UserResponseCode`: `05001` für `LoginSuccessful`, `05002` für `CredentialsError`. `SystemResponseCode` verwendet `00` für allgemeinen Erfolg und HTTP-Fehler, z. B. `00404` für eine unbekannte Route. Codes stets als Strings behandeln.

```json
{"code":"00001","status":true,"data":{"status":"ok"},"responseStatus":200}
```

Die eigenen JSON-Endpunkte `/` und `/api/*` sowie deren zentral behandelte Fehler nutzen dieses Format. Validierungsfehler stehen in `data.errors`, Fehlermeldungen in `data.message`; HTTP-Header wie `Retry-After` bleiben erhalten. Serverfehler geben keine internen Exception-Details aus. Browser-/Filament-/Runner-Antworten und der Laravel-Healthcheck `/up` behalten ihr jeweiliges Protokoll. Die User-Codes sind vorbereitet; es wurde keine API-Login-Route angelegt.

## Controller und Actions

Eigene Controller enthalten ausschließlich die vom Router aufgerufenen Methoden. Jede Methode erhält ihre Action per typisierter Methodeninjektion und gibt deren `handle()`-Ergebnis zurück. Die Logik einschließlich Validierung und Antworterzeugung liegt in der Action.

Actions liegen unter `app/Actions/<Controllerbereich>/`. Der Bereich ist der Controllername ohne `Controller` im Plural, beispielsweise `UserController` → `Users`, `SessionController` → `Sessions`. Der Klassenname entspricht der Methode in PascalCase mit dem Zusatz `Action`: `login` → `LoginAction`, `store` → `StoreAction`.

```php
public function store(Request $request, StoreAction $action): RedirectResponse
{
    return $action->handle($request);
}
```

Die vorhandene Session-Anmeldung nutzt `app/Actions/Sessions/`. Konkrete Action-Klassen werden automatisch durch Laravels Container aufgelöst; kein manuelles `new` oder zusätzliches Binding nötig. Controller erhalten keine Hilfsmethoden mit Anwendungslogik. Für neue Controller dasselbe Muster verwenden.

## Konfiguration

Lokale Einstellungen stehen in `.env`, Standardwerte in `.env.example`. `.env` und `vendor/` sind vom Git-Tracking ausgeschlossen. Keine Zugangsdaten einchecken. Auf produktiven Systemen `APP_DEBUG=false` setzen.
