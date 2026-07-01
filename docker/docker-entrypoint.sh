#!/bin/sh
set -e

echo "Waiting for database to be ready..."

until php -r "\$fp = @fsockopen('database', 5432, \$errno, \$errstr, 2); if (\$fp) { fclose(\$fp); exit(0); } else { exit(1); }" > /dev/null 2>&1; do
  sleep 1
done

echo "Database is ready! Starting the application..."
exec "$@"
