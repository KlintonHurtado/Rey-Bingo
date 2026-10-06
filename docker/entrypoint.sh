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

for ENV_FILE in "/var/www/html/reybingo.com/public_html/.env"; do
    if [ -f "$ENV_FILE" ]; then
        sed -i "s|^CI_ENVIRONMENT.*|CI_ENVIRONMENT = production|g" "$ENV_FILE"
        sed -i "s|^database.default.hostname.*|database.default.hostname = $TARGET_HOST|g" "$ENV_FILE"
        sed -i "s|^database.default.database.*|database.default.database = $TARGET_DB|g" "$ENV_FILE"
        sed -i "s|^database.default.username.*|database.default.username = $TARGET_USER|g" "$ENV_FILE"
        if [ -n "$TARGET_PASS" ]; then
            ESCAPED_PASS=$(printf '%s\n' "$TARGET_PASS" | sed -e 's/[\/&]/\\&/g')
            sed -i "s|^database.default.password.*|database.default.password = $ESCAPED_PASS|g" "$ENV_FILE"
        fi
        sed -i "s|^database.default.port.*|database.default.port = $TARGET_PORT|g" "$ENV_FILE"
    fi
done

# Ensure bingo-runner daemon targets local Apache inside the container
RUNNER_ENV="/var/www/html/reybingo.com/public_html/bingo-runner/.env"
if [ -f "$RUNNER_ENV" ]; then
    sed -i "s|^APP_URL=.*|APP_URL=http://127.0.0.1|g" "$RUNNER_ENV"
    sed -i "s|^CRON_TOKEN=.*|CRON_TOKEN=reybingo_cron_secret_key_2026|g" "$RUNNER_ENV"
fi

# Execute CMD (supervisord)
exec "$@"
