#!/usr/bin/env bash

docker exec brux_system_test_php dos2unix bin/console
docker exec brux_system_test_php composer install --no-dev --optimize-autoloader
