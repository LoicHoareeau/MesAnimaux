FROM composer:2 AS composer

FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/* \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/render-site.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

ENV APP_ENV=prod APP_DEBUG=0

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && php bin/console importmap:install --no-interaction \
    && php bin/console asset-map:compile --no-interaction \
    && mkdir -p var \
    && chown -R www-data:www-data var

EXPOSE 10000

CMD ["sh", "docker/render-start.sh"]
