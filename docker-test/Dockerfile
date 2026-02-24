FROM php:8.3-fpm

# System-Dependencies (z.B. für Doctrine-DBAL, ZIP-Handling, ext-Installationen)
RUN apt-get update \
  && apt-get install -y git unzip zip libicu-dev libonig-dev libjpeg-dev libpng-dev zlib1g-dev dos2unix \
  && docker-php-ext-install pdo_mysql intl opcache gd pcntl

# Composer vom offiziellen Composer-Image übernehmen
COPY --from=composer:2.5 /usr/bin/composer /usr/local/bin/composer

# (Optional) Composer-Cache-Dir festlegen
ENV COMPOSER_HOME=/var/www/.composer

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var \
 && chmod -R 775 /var \
