# OJT Sync production image — PHP 8.5 + Apache + Postgres driver.
#
# Render has no native PHP runtime, so this app ships as a Docker image.
# Vite assets are prebuilt and committed in public/build, so no Node is
# needed at build time. The Neon connection (pgsql) driver is installed
# below; the connection string itself comes from the DB_URL env var at
# runtime.

# PHP 8.5 matches the local dev toolchain. The locked dependencies
# (Symfony 8.1 / Laravel 13.24) require PHP >= 8.4.1, so php:8.3 would
# fail `composer install` with an incompatible-platform error.
FROM php:8.5-apache

# System packages + PHP extensions:
#   pdo_pgsql -> Neon Postgres (DB_URL)
#   zip/bcmath -> Composer + defensive defaults for Laravel
#   unzip     -> Composer dist extraction
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install pdo_pgsql zip bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer (official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy the app (node_modules, vendor, .env are excluded via .dockerignore)
COPY . .

# Install production dependencies and prepare Laravel's storage links.
# (No APP_KEY/DB_URL exist at build time, so no config:cache here.)
RUN composer install --no-dev --optimize-autoloader \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

# Point Apache at Laravel's public/ dir and enable mod_rewrite.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

EXPOSE 80

# Run migrations on boot (idempotent), then start Apache.
# To seed demo data once, temporarily append "&& php artisan db:seed --force".
CMD ["sh", "-c", "chmod -R 775 bootstrap/cache storage && chown -R www-data:www-data bootstrap/cache storage && php artisan migrate --force --no-interaction && php artisan db:seed --force && apache2-foreground"]
