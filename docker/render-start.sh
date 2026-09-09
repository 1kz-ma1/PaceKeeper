#!/usr/bin/env sh
set -eu

cd /var/www/html

# Ensure Laravel's writable directories exist inside each fresh container.
mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Run database migrations only when explicitly enabled in Render.
# Set RUN_MIGRATIONS=true after the Aiven connection variables are configured.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Running Laravel migrations..."
  php artisan migrate --force
fi

# Cache production configuration on container startup so runtime environment
# variables (APP_URL, DB_*, etc.) are included.
php artisan config:cache
php artisan route:cache
php artisan view:cache

PORT="${PORT:-10000}"

echo "Starting PaceKeeper on 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
