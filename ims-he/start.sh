#!/bin/sh
set -e
cd /app

# Listen on the port the platform gives us (Railway sets PORT), default 8080.
export SERVER_NAME=":${PORT:-8080}"

php artisan storage:link 2>/dev/null || true
php artisan migrate --force
php artisan optimize

# exec so FrankenPHP becomes the main process
exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
