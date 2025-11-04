#!/usr/bin/env bash

docker exec brux_system_php dos2unix bin/console
docker exec brux_system_php composer install --no-dev --optimize-autoloader
