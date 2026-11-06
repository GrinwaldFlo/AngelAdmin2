#!/usr/bin/env bash
# Run the maintenance required after updating the code (e.g. after a git pull)
set -euo pipefail

cd "$(dirname "$0")"

echo "Installing dependencies"
# Keep dev packages: config/bootstrap.php needs josegonzalez/dotenv to load .env
composer install --no-interaction --optimize-autoloader

echo "Migrating database"
# --no-lock: don't rewrite the tracked schema-dump lock file, it would block the next git pull
bin/cake migrations migrate --no-lock

echo "Clearing caches"
bin/cake cache clear_all

echo "Clearing schema cache"
bin/cake schema_cache clear

echo "Update done"
