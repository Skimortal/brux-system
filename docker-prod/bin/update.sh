#!/usr/bin/env bash
docker exec brux_system_php dos2unix bin/console
docker exec brux_system_php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker exec brux_system_php bin/console cache:clear --env=prod
docker exec brux_system_php bin/console cache:warmup --env=prod

docker exec brux_system_nginx chown -R www-data:www-data var/
