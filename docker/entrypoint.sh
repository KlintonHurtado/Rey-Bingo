#!/bin/bash
set -e

# Fix permissions on writable directory for CodeIgniter 4
mkdir -p /var/www/html/reybingo.com/public_html/writable/cache \
         /var/www/html/reybingo.com/public_html/writable/logs \
         /var/www/html/reybingo.com/public_html/writable/session \
         /var/www/html/reybingo.com/public_html/writable/uploads

chown -R www-data:www-data /var/www/html/reybingo.com/public_html/writable
chmod -R 777 /var/www/html/reybingo.com/public_html/writable

# Execute CMD (supervisord)
exec "$@"
