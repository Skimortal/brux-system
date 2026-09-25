#!/usr/bin/env bash
set -euo pipefail

docker exec brux_system_php dos2unix bin/console
docker exec brux_system_php composer install --no-dev --optimize-autoloader --no-interaction
docker exec brux_system_php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker exec brux_system_php bin/console importmap:install --env=prod
docker exec brux_system_php bin/console asset-map:compile --env=prod
docker exec brux_system_php bin/console cache:clear --env=prod
docker exec brux_system_php bin/console cache:warmup --env=prod

docker exec brux_system_nginx chown -R www-data:www-data var/ public/assets/
