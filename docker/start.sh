#!/bin/sh
# OJT Sync container boot sequence.
#
# Design rule: the web server must ALWAYS start. Render shows an opaque
# 502 when nothing answers inside the container, so a failed migration
# must log a warning and continue instead of crash-looping the service.

set -u

PORT="${PORT:-80}"

# Laravel writable dirs (storage/, bootstrap/cache/)
chmod -R 775 bootstrap/cache storage || true
chown -R www-data:www-data bootstrap/cache storage || true

# Migrate + seed. Failures are logged but never block Apache — the real
# error stays visible in the Render log viewer.
if php artisan migrate --force --no-interaction; then
    php artisan db:seed --force || echo "[boot] WARNING: seeding failed — see errors above"
else
    echo "[boot] WARNING: migration FAILED (check DB_URL) — starting Apache anyway"
fi

# Bind Apache (and the default vhost) to $PORT. Render forwards traffic to
# its PORT env var (default 10000); the stock php:apache image only listens
# on 80, which breaks Render's routing if autodetection misses.
sed -ri "s/^Listen 80\$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "[boot] starting Apache on port ${PORT}"
exec apache2-foreground
