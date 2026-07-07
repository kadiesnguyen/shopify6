#!/usr/bin/env bash
# Safe storage cleanup for production deploy path. Does not touch DB rows or catalog images in use.
set -euo pipefail

ROOT="${REMOTE_PATH:-/www/wwwroot/shopjfy6.com}"
KEEP_DB_BACKUPS="${KEEP_DB_BACKUPS:-2}"
REMOVE_NODE_MODULES="${REMOVE_NODE_MODULES:-1}"

cd "$ROOT"

echo "==> Cleanup at $ROOT"

if command -v php >/dev/null 2>&1 && [[ -f artisan ]]; then
  php artisan view:clear --no-interaction 2>/dev/null || true
  php artisan cache:clear --no-interaction 2>/dev/null || true
fi

# Orphan flat demo thumbs (legacy products/demo/*.jpg before per-goods folders).
if command -v php >/dev/null 2>&1 && [[ -f artisan ]]; then
  php artisan tinker --execute="
    \$used = App\Models\Product::query()->whereNotNull('image')->pluck('image')
      ->merge(App\Models\ProductImage::query()->pluck('image'))
      ->flip();
    \$dir = storage_path('app/public/products/demo');
    \$removed = 0;
    foreach (glob(\$dir.'/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as \$file) {
      \$rel = 'products/demo/'.basename(\$file);
      if (! isset(\$used[\$rel])) { @unlink(\$file); \$removed++; }
    }
    echo \"removed_orphan_demo_flat={\$removed}\n\";
  " 2>/dev/null || true
fi

BACKUP_DIR="$ROOT/storage/app/backups/database"
if [[ -d "$BACKUP_DIR" ]]; then
  mapfile -t OLDBACKUPS < <(ls -1t "$BACKUP_DIR"/*.sql 2>/dev/null || true)
  if ((${#OLDBACKUPS[@]} > KEEP_DB_BACKUPS)); then
    for ((i = KEEP_DB_BACKUPS; i < ${#OLDBACKUPS[@]}; i++)); do
      rm -f "${OLDBACKUPS[$i]}"
      echo "removed backup ${OLDBACKUPS[$i]}"
    done
  fi
fi

# Built assets exist — node_modules only needed during deploy build.
if [[ "$REMOVE_NODE_MODULES" == "1" && -d node_modules && -f public/build/manifest.json ]]; then
  rm -rf node_modules
  echo "removed node_modules"
fi

# Trim huge app log (keep file, drop body).
if [[ -f storage/logs/laravel.log ]]; then
  : > storage/logs/laravel.log
  echo "truncated storage/logs/laravel.log"
fi

du -sh storage/app/public/products storage/logs storage/app/backups "$ROOT" 2>/dev/null | sed "s|^|$ROOT → |"
