#!/usr/bin/env bash
set -e

cd /var/www/html

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan octane:start \
  --server=swoole \
  --host=0.0.0.0 \
  --port="${OCTANE_PORT:-8080}" \
  --workers="${OCTANE_WORKERS:-4}" \
  --max-requests="${OCTANE_MAX_REQUESTS:-500}"
