#!/bin/sh
set -e

# Render (and most PaaS Docker hosts) inject $PORT and expect the container
# to listen on it — it varies per deploy, so Apache's fixed "Listen 80"
# needs rewriting at boot rather than build time.
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set. Generating one for this boot only —"
    echo "sessions will invalidate on every restart. Set APP_KEY as a"
    echo "persistent env var in your host's dashboard instead (see docs/STAGING.md)."
    export APP_KEY=$(php artisan key:generate --show)
fi

php artisan migrate --force

php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
