# Projekt Setup

## 1. Anpassen der docker/.env (von env.tmp kopieren)

## 2. Anpassen der vhost.conf

Statt "php:9000" => "container_php:9000"

```
fastcgi_pass php:9000;
```

## 3. Anpassen der APP/.env.local mit neuem secret

Generieren eines secrets
```
openssl rand -hex 32
```

Anpassen der .env.local
```
DATABASE_URL="mysql://DB_PW:DB_USER@DB_HOST:3306/symfony?serverVersion=8.0&charset=utf8mb4"
APP_SECRET="insert_generated_secret"
MAILER_DSN=smtp://brux_system_mail:1025
```

## 4. im Docker-Ordner

To initialize the docker environment with admin user execute following command:

```
cd docker
make
cd ..
```

## Frontend (seit dem Redesign)

Kein Node-Build mehr: Styles und JavaScript laufen über Symfony AssetMapper
(`APP/assets/`, Importmap in `APP/importmap.php`). Icons liegen lokal unter
`APP/assets/icons/`, Schriften unter `APP/assets/fonts/`.

Beim Deploy (siehe `docker-test/bin/update.sh`):

```
composer install --no-dev --optimize-autoloader
bin/console doctrine:migrations:migrate --no-interaction --env=prod
bin/console importmap:install --env=prod
bin/console asset-map:compile --env=prod
bin/console cache:clear --env=prod
```

### WordPress

| Variable | Zweck |
|---|---|
| `BRUX_API_URL` | Import-Endpunkt (Standard: `https://www.brux.at/wp-json/brux/v1/events`) |
| `WORDPRESS_API_URL` | Rückweg (PUT `/production`, `/event`); leer = kein Push |
| `WORDPRESS_API_TOKEN` | Bearer-Token für den Rückweg |

Welche Felder WordPress gehören, steht in `APP/config/services.yaml`
(`wordpress_owned_fields`). Diese Felder sind im Haussystem nur lesbar und werden
beim Import überschrieben. Soll das Haussystem ein Feld führen, entfernt man es
aus der Liste. Die Formulare schalten das Feld dann frei (Produktion: Titel,
Link, Bild samt Upload, Kurztext, Beschreibung, Preise; Vorstellung: Datum,
Zeiten, Raum, Status, Plätze, Kategorien, Preise), und Speichern überträgt es an
WordPress. Sind beide Listen leer, führt das Haussystem vollständig: Vorstellungen
werden dann auch für WordPress-Produktionen hier angelegt und gelöscht. Eigene
Produktionen (ohne WordPress-ID) sind immer vollständig bearbeitbar.

Die Beschreibung wird mit einem Editor (TipTap) gepflegt und beim Speichern
bereinigt (`APP/config/packages/html_sanitizer.yaml`): Absätze, Überschriften,
Listen, Zitate, Links und Bilder bleiben, Klassen, Styles und Skripte nicht.
Solange WordPress das Feld führt, zeigt der Editor den Text nur an.

Hochgeladene Produktionsbilder liegen in `APP/public/uploads/productions/`
(nicht im Git) und werden mit absoluter URL gespeichert, damit WordPress sie laden kann.

Import: Cronjob `app:sync-brux-api` (siehe `docs/cron-setup.md`), Button im
Kalender, und nach dem ersten Login, wenn der letzte Import älter als 24 h ist.

### Benutzer anlegen

```
bin/console app:user:create <benutzername> <email> --role=ROLE_ADMIN --first-name=… --last-name=…
```
