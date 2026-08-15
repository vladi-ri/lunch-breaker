#!/usr/bin/env bash
#
# Polled by cron every few minutes on the production host. Pulls new commits
# from origin/main and, only if there actually are any, reinstalls
# dependencies, rebuilds frontend assets, runs pending migrations, clears
# cached config, and rebuilds/recreates the app/web/scheduler/queue
# containers — a plain restart wouldn't pick up a Dockerfile change.
#
# vendor/, node_modules/ and public/build/ are all gitignored and bind-mounted
# into the containers rather than baked into the image, so none of them are
# ever up to date on their own after a plain `git pull` — this script exists
# specifically to stop that from being a manual, easy-to-forget step.
#
# Safe to run concurrently with itself (flock) and safe to run when nothing
# changed (exits immediately after the fetch).

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

LOCK_FILE="/tmp/lunch-breaker-deploy.lock"
exec 9>"${LOCK_FILE}"
if ! flock -n 9; then
    echo "$(date -Iseconds) deploy already in progress, skipping"
    exit 0
fi

git fetch origin main

LOCAL_COMMIT="$(git rev-parse HEAD)"
REMOTE_COMMIT="$(git rev-parse origin/main)"

if [ "${LOCAL_COMMIT}" = "${REMOTE_COMMIT}" ]; then
    exit 0
fi

echo "$(date -Iseconds) deploying ${LOCAL_COMMIT} -> ${REMOTE_COMMIT}"

git pull origin main

docker compose exec -T app composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
docker run --rm -v "$(pwd)":/app -w /app node:20 sh -c "npm install && npm run build"
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan config:clear

docker compose up -d --build app web scheduler queue

echo "$(date -Iseconds) deploy finished at $(git rev-parse HEAD)"
