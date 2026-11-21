#!/bin/sh
set -eu

APP_ROOT="${APP_ROOT:-/var/www/html}"

# Laravel needs these directories to exist even when storage is a fresh volume.
mkdir -p \
    "$APP_ROOT/bootstrap/cache" \
    "$APP_ROOT/storage/app/public" \
    "$APP_ROOT/storage/framework/cache/data" \
    "$APP_ROOT/storage/framework/sessions" \
    "$APP_ROOT/storage/framework/views" \
    "$APP_ROOT/storage/logs"

# Only root can repair ownership; production runs as www-data and skips this.
if [ "$(id -u)" = "0" ] && [ -d "$APP_ROOT/storage" ]; then
    chown -R www-data:www-data "$APP_ROOT/storage" "$APP_ROOT/bootstrap/cache" 2>/dev/null || true
fi

exec "$@"
