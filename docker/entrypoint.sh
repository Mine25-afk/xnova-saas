#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY no está definido. Genera uno con: docker compose run --rm app php artisan key:generate --show" >&2
    exit 1
fi

php artisan storage:link >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
