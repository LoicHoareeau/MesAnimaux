#!/bin/sh
set -eu

: "${DATABASE_URL:?Set DATABASE_URL to the Render Postgres internal URL}"

# Use PostgreSQL 16 as the baseline when the Render URL omits a platform version.
case "$DATABASE_URL" in
    *\?serverVersion=*|*\&serverVersion=*) ;;
    *\?*) export DATABASE_URL="$DATABASE_URL&serverVersion=16.0" ;;
    *) export DATABASE_URL="$DATABASE_URL?serverVersion=16.0" ;;
esac

su -s /bin/sh www-data -c 'php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration'
if [ "${LOAD_DEMO_CATALOGUE:-0}" = "1" ]; then
    su -s /bin/sh www-data -c 'php bin/console app:catalogue:demo --no-interaction'
fi

exec apache2-foreground
