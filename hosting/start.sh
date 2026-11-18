#!/bin/sh
set -eu
cd /app/backend
export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
export APP_URL="${RENDER_EXTERNAL_URL:-http://localhost:8080}"
export DB_DATABASE=/app/backend/database/hosted-template.sqlite
rm -f "$DB_DATABASE"
touch "$DB_DATABASE"
php artisan migrate --seed --force --quiet
php /app/hosting/seed-template.php
chown -R www-data:www-data /app/backend/storage /app/backend/database
exec apache2-foreground
