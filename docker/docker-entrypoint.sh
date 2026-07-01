#!/bin/sh
set -e

echo "Waiting for database to be ready..."

until php -r "\$fp = @fsockopen('database', 5432, \$errno, \$errstr, 2); if (\$fp) { fclose(\$fp); exit(0); } else { exit(1); }" > /dev/null 2>&1; do
  sleep 1
done

echo "Database is ready! Running Symfony commands..."

php bin/console doctrine:database:create --if-not-exists --no-interaction
php bin/console doctrine:schema:update --force --no-interaction

echo "Migrations and schema update completed. Starting the application..."
exec "$@"
