#!/bin/sh
set -e

cd /var/www/html

# Install PHP dependencies on first boot (vendor/ is not committed)
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Make sure Laravel's writable dirs exist and are writable
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache storage/app/keypairs
chmod -R ug+rwX storage bootstrap/cache || true

exec "$@"
