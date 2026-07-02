#!/bin/sh
set -e

echo "Waiting for database to be ready..."

DB_HOST_NAME=${DB_HOST_NAME:-${DB_HOST:-${PGHOST:-database}}}
DB_PORT=${DB_PORT:-${PGPORT:-5432}}
DB_WAIT_MAX_RETRIES=${DB_WAIT_MAX_RETRIES:-60}
DB_WAIT_RETRY=0

echo "Checking database host ${DB_HOST_NAME}:${DB_PORT}"

until php -r "\$fp = @fsockopen('$DB_HOST_NAME', (int) '$DB_PORT', \$errno, \$errstr, 2); if (\$fp) { fclose(\$fp); exit(0); } else { exit(1); }" > /dev/null 2>&1; do
  DB_WAIT_RETRY=$((DB_WAIT_RETRY + 1))

  if [ "$DB_WAIT_RETRY" -ge "$DB_WAIT_MAX_RETRIES" ]; then
    echo "Database is not reachable after ${DB_WAIT_MAX_RETRIES} attempts (${DB_HOST_NAME}:${DB_PORT})."
    exit 1
  fi

  sleep 1
done

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Clearing Symfony cache..."
APP_DEBUG=1 php bin/console cache:clear

echo "Finished preparing application"

echo "Database is ready! Starting the application..."
exec "$@"
