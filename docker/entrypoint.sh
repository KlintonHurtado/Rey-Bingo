#!/bin/bash
set -e

# Fix permissions on writable directory for CodeIgniter 4
mkdir -p /var/www/html/reybingo.com/public_html/writable/cache \
         /var/www/html/reybingo.com/public_html/writable/logs \
         /var/www/html/reybingo.com/public_html/writable/session \
         /var/www/html/reybingo.com/public_html/writable/uploads

chown -R www-data:www-data /var/www/html/reybingo.com/public_html/writable
chmod -R 777 /var/www/html/reybingo.com/public_html/writable

# Dynamic Database configuration injection
TARGET_HOST="${DB_HOST:-${database_default_hostname:-rey-bingo-database-ko9tep}}"
TARGET_DB="${DB_DATABASE:-${database_default_database:-reybingo}}"
TARGET_USER="${DB_USERNAME:-${database_default_username:-mysql}}"
TARGET_PASS="${DB_PASSWORD:-${database_default_password:-}}"
TARGET_PORT="${DB_PORT:-${database_default_port:-3306}}"

for ENV_FILE in "/var/www/html/reybingo.com/public_html/.env" "/var/www/html/reybingo.com/public_html/vnzl/.env"; do
    if [ -f "$ENV_FILE" ]; then
        sed -i "s|^database.default.hostname.*|database.default.hostname = $TARGET_HOST|g" "$ENV_FILE"
        sed -i "s|^database.default.database.*|database.default.database = $TARGET_DB|g" "$ENV_FILE"
        sed -i "s|^database.default.username.*|database.default.username = $TARGET_USER|g" "$ENV_FILE"
        [ -n "$TARGET_PASS" ] && sed -i "s|^database.default.password.*|database.default.password = $TARGET_PASS|g" "$ENV_FILE"
        sed -i "s|^database.default.port.*|database.default.port = $TARGET_PORT|g" "$ENV_FILE"
    fi
done

# Execute CMD (supervisord)
exec "$@"
