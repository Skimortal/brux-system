#!/usr/bin/env bash
set -euo pipefail

# Containers are found by the APP directory they mount, so the script works in any
# checkout (test and prod use different container names).
APP_DIR=$(realpath "$(dirname "$0")/../../APP")

container() {
    local id src
    for id in $(docker ps -q --filter "label=com.docker.compose.service=$1"); do
        src=$(docker inspect -f '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}' "$id")
        if [ -n "$src" ] && [ "$(realpath "$src")" = "$APP_DIR" ]; then
            docker inspect -f '{{.Name}}' "$id" | sed 's#^/##'
            return
        fi
    done
    echo "No running '$1' container mounts $APP_DIR" >&2
    exit 1
}

PHP=$(container php)
NGINX=$(container nginx)
WORKER=$(container worker)
echo "Updating $PHP, $NGINX, $WORKER"

docker exec "$PHP" dos2unix bin/console
docker exec -e COMPOSER_ALLOW_SUPERUSER=1 "$PHP" composer install --no-dev --optimize-autoloader --no-interaction
docker exec "$PHP" bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker exec "$PHP" bin/console importmap:install --env=prod
docker exec "$PHP" bin/console asset-map:compile --env=prod
docker exec "$PHP" bin/console cache:clear --env=prod
docker exec "$PHP" bin/console cache:warmup --env=prod

docker exec -w /var/www/html "$NGINX" mkdir -p public/uploads/productions
docker exec -w /var/www/html "$NGINX" chown -R www-data:www-data var/ public/assets/ public/uploads/
docker restart "$WORKER"
