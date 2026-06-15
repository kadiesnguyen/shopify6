#!/usr/bin/env bash
# Deploy Shopefy: export local DB → git push → server pull → migrate → import DB
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-$(dirname "$ROOT")/backups}"
SSH_HOST="${SSH_HOST:-ServerSand}"
REMOTE_PATH="${REMOTE_PATH:-/www/wwwroot/shopjfy6.com}"
GIT_BRANCH="${GIT_BRANCH:-main}"
MYSQL_CONTAINER="${MYSQL_CONTAINER:-shopefy-mysql-1}"
SKIP_GIT_PUSH="${SKIP_GIT_PUSH:-0}"
SKIP_DB_EXPORT="${SKIP_DB_EXPORT:-0}"
SKIP_DB_IMPORT="${SKIP_DB_IMPORT:-1}"
if [[ -z "${SKIP_STORAGE_SYNC:-}" ]]; then
    if [[ "$SKIP_DB_IMPORT" == "0" ]]; then
        SKIP_STORAGE_SYNC=0
    else
        SKIP_STORAGE_SYNC=1
    fi
fi
DUMP_FILE=""

mkdir -p "$BACKUP_DIR"

log() { printf '\n[%s] %s\n' "$(date +%H:%M:%S)" "$*"; }

export_local_db() {
    if [[ "$SKIP_DB_EXPORT" == "1" ]]; then
        log "Skip DB export (SKIP_DB_EXPORT=1)"
        return
    fi

    local stamp dump
    stamp="$(date +%Y%m%d_%H%M%S)"
    dump="$BACKUP_DIR/shopefy_${stamp}.sql"

    if docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "$MYSQL_CONTAINER"; then
        log "Export DB from Docker container: $MYSQL_CONTAINER"
        docker exec "$MYSQL_CONTAINER" mysqldump \
            -ushopefy -pshopefy \
            --single-transaction --routines --triggers \
            shopefy 2>/dev/null | sed '/^mysqldump:/d' > "$dump"
    elif command -v mysqldump >/dev/null 2>&1 && [[ -f "$ROOT/.env" ]]; then
        log "Export DB from local mysqldump + .env"
        # shellcheck disable=SC1091
        set -a && source "$ROOT/.env" && set +a
        mysqldump -h"${DB_HOST:-127.0.0.1}" -P"${DB_PORT:-3306}" \
            -u"$DB_USERNAME" -p"$DB_PASSWORD" \
            --single-transaction --routines --triggers \
            "$DB_DATABASE" 2>/dev/null | sed '/^mysqldump:/d' > "$dump"
    else
        log "WARN: No Docker MySQL and no local mysqldump — skipping export"
        return
    fi

    if [[ ! -s "$dump" ]]; then
        echo "ERROR: DB export failed or empty: $dump" >&2
        exit 1
    fi

    DUMP_FILE="$dump"
    log "DB exported: $DUMP_FILE ($(du -h "$DUMP_FILE" | awk '{print $1}'))"
}

git_push() {
    if [[ "$SKIP_GIT_PUSH" == "1" ]]; then
        log "Skip git push (SKIP_GIT_PUSH=1)"
        return
    fi

    log "Git push origin $GIT_BRANCH"
    cd "$ROOT"
    if ! git diff --quiet || ! git diff --cached --quiet || [[ -n "$(git status --porcelain)" ]]; then
        echo "ERROR: Working tree has uncommitted changes. Commit first." >&2
        git status --short
        exit 1
    fi
    git push -u origin "$GIT_BRANCH"
}

remote_pull_and_build() {
    log "Server: git pull + composer + npm + migrate + cache"
    ssh -o BatchMode=yes "$SSH_HOST" "REMOTE_PATH='$REMOTE_PATH' GIT_BRANCH='$GIT_BRANCH' bash -s" <<'REMOTE'
set -euo pipefail
cd "$REMOTE_PATH"

git -c "safe.directory=$REMOTE_PATH" fetch origin "$GIT_BRANCH"
git -c "safe.directory=$REMOTE_PATH" checkout "$GIT_BRANCH"
git -c "safe.directory=$REMOTE_PATH" pull origin "$GIT_BRANCH"

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force
php artisan storage:link 2>/dev/null || true

mkdir -p \
  storage/app/public/avatars \
  storage/app/public/shops \
  storage/app/public/chat \
  storage/app/public/products \
  storage/app/public/cms/pages \
  storage/app/public/site-settings \
  storage/app/public/shop-applications/logos \
  storage/app/public/shop-applications/id \
  storage/app/private/private/shops \
  storage/app/backups/database \
  public/uploads/avatars \
  public/uploads/shops

chown -R www:www storage bootstrap/cache public/uploads 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache public/uploads 2>/dev/null || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Remote app updated at $(pwd) @ $(git -c safe.directory=$REMOTE_PATH rev-parse --short HEAD)"
REMOTE
}

import_db_on_server() {
    if [[ "$SKIP_DB_IMPORT" == "1" ]]; then
        log "Skip DB import (default — production data preserved). Set SKIP_DB_IMPORT=0 to overwrite."
        return
    fi

    if [[ -z "$DUMP_FILE" || ! -f "$DUMP_FILE" ]]; then
        log "WARN: No dump file — skipping import"
        return
    fi

    log "Backup production DB before import"
    ssh -o BatchMode=yes "$SSH_HOST" "REMOTE_PATH='$REMOTE_PATH' bash -s" <<'REMOTE'
set -euo pipefail
cd "$REMOTE_PATH"
mkdir -p storage/app/backups/database
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"' ')
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"' ')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'"' ')
STAMP=$(date +%Y%m%d_%H%M%S)
BACKUP="storage/app/backups/database/shopefy_pre_deploy_${STAMP}.sql"
MYSQL_PWD="$DB_PASSWORD" mysqldump -u"$DB_USERNAME" \
  --single-transaction --routines --triggers \
  "$DB_DATABASE" > "$BACKUP"
echo "Production backup: $BACKUP ($(du -h "$BACKUP" | awk '{print $1}'))"
REMOTE

    local remote_dump="/tmp/shopefy_import_$(date +%s).sql"
    log "Upload dump → $SSH_HOST:$remote_dump"
    scp -o BatchMode=yes "$DUMP_FILE" "$SSH_HOST:$remote_dump"

    log "Import DB on server"
    ssh -o BatchMode=yes "$SSH_HOST" "REMOTE_PATH='$REMOTE_PATH' REMOTE_DUMP='$remote_dump' bash -s" <<'REMOTE'
set -euo pipefail
cd "$REMOTE_PATH"

DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"' ')
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"' ')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'"' ')

if [[ -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
  echo "ERROR: Cannot read DB_* from $REMOTE_PATH/.env" >&2
  exit 1
fi

mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$REMOTE_DUMP"
rm -f "$REMOTE_DUMP"
php artisan shops:sync-roles --no-interaction
echo "DB imported into $DB_DATABASE (skipped db:ensure-ready — full dump already contains users)"
REMOTE
}

sync_storage_to_server() {
    if [[ "$SKIP_STORAGE_SYNC" == "1" ]]; then
        log "Skip storage sync (default). Set SKIP_STORAGE_SYNC=0 after DB import to upload media files."
        return
    fi

    log "Sync storage/app/public + public/uploads to server"
    SSH_HOST="$SSH_HOST" REMOTE_PATH="$REMOTE_PATH" "$ROOT/scripts/sync-storage-to-server.sh"
}

main() {
    log "=== Deploy start ==="
    export_local_db
    git_push
    remote_pull_and_build
    import_db_on_server
    sync_storage_to_server
    log "=== Deploy done ==="
    log "Site: https://shopjfy6.com"
}

main "$@"
