#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BACKUP_DIR="${BACKUP_DIR:-$ROOT/storage/app/backups/database}"
MYSQL_CONTAINER="${MYSQL_CONTAINER:-shopefy-mysql-1}"

mkdir -p "$BACKUP_DIR"

if docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "$MYSQL_CONTAINER"; then
  if docker compose ps --status running app >/dev/null 2>&1; then
    docker compose exec -T app php artisan db:backup --path="$BACKUP_DIR"
    exit 0
  fi

  stamp="$(date +%Y%m%d_%H%M%S)"
  dump="$BACKUP_DIR/shopefy_${stamp}.sql"
  docker exec "$MYSQL_CONTAINER" mysqldump \
    -ushopefy -pshopefy \
    --single-transaction --routines --triggers \
    shopefy 2>/dev/null | sed '/^mysqldump:/d' > "$dump"
  echo "Backup saved: $dump ($(du -h "$dump" | awk '{print $1}'))"
  exit 0
fi

if command -v php >/dev/null 2>&1; then
  php artisan db:backup --path="$BACKUP_DIR"
  exit 0
fi

echo "ERROR: No Docker MySQL or local php available for backup." >&2
exit 1
