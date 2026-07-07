#!/usr/bin/env bash
# Safe whole-server cleanup (logs, deploy artifacts, caches). Does not delete DB/uploads/order data.
set -euo pipefail

FREED=0

note_freed() {
  local label="$1"
  local bytes="$2"
  FREED=$((FREED + bytes))
  echo "freed ~$(numfmt --to=iec-i --suffix=B "$bytes" 2>/dev/null || echo "${bytes}B") — $label"
}

size_of() {
  local path="$1"
  if [[ -e "$path" ]]; then
    du -sb "$path" 2>/dev/null | awk '{print $1}'
  else
    echo 0
  fi
}

truncate_logs() {
  local pattern="$1"
  shopt -s nullglob
  for f in $pattern; do
    [[ -f "$f" ]] || continue
    local before
    before=$(stat -c%s "$f" 2>/dev/null || echo 0)
    : >"$f"
    note_freed "$(basename "$f")" "$before"
  done
}

echo "==> Server-wide storage cleanup $(date -Iseconds)"

# 1) Nginx / panel access logs (truncate, keep inode for writers).
truncate_logs "/www/wwwlogs/*access_log*"
truncate_logs "/www/wwwlogs/access_log"
truncate_logs "/www/wwwlogs/error_log"

# 2) Deploy zip archives left in web roots.
shopt -s nullglob
for zip in /www/wwwroot/*/*.zip /www/wwwroot/*/*/*.zip; do
  [[ -f "$zip" ]] || continue
  local_before=$(size_of "$zip")
  rm -f "$zip"
  note_freed "$zip" "$local_before"
done

# 3) node_modules on PHP/Laravel sites only (not Next/PM2 apps).
for site in /www/wwwroot/*/; do
  [[ -d "${site}node_modules" ]] || continue
  base=$(basename "$site")
  case "$base" in
    wnskcex.com|sandnode|cms.wnskcex.com|shopjfy6.com) continue ;;
  esac
  if [[ -f "${site}artisan" ]]; then
    before=$(size_of "${site}node_modules")
    rm -rf "${site}node_modules"
    note_freed "${base}/node_modules" "$before"
  fi
done

# 4) Old panel PHP config backups (May 2025).
for bak in /www/backup/php-fpm84.Bak /www/backup/php84.Bak; do
  [[ -f "$bak" ]] || continue
  before=$(size_of "$bak")
  rm -f "$bak"
  note_freed "$(basename "$bak")" "$before"
done

# 5) System caches / noisy logs.
if command -v apt-get >/dev/null 2>&1; then
  before=$(du -sb /var/cache/apt/archives 2>/dev/null | awk '{print $1}')
  apt-get clean -y >/dev/null 2>&1 || true
  note_freed "apt cache" "${before:-0}"
fi

if command -v npm >/dev/null 2>&1; then
  npm cache clean --force >/dev/null 2>&1 || true
  note_freed "npm cache" "$(size_of /root/.npm)"
fi

truncate_logs "/var/log/btmp"
truncate_logs "/var/log/btmp.1"
truncate_logs "/var/log/auth.log.1"

if command -v journalctl >/dev/null 2>&1; then
  journalctl --vacuum-time=14d >/dev/null 2>&1 || true
  echo "journal vacuumed (14d retention)"
fi

# 6) Per-site Shopefy cleanup if present.
if [[ -x /www/wwwroot/shopjfy6.com/scripts/server-storage-cleanup.sh ]]; then
  REMOTE_PATH=/www/wwwroot/shopjfy6.com bash /www/wwwroot/shopjfy6.com/scripts/server-storage-cleanup.sh || true
fi

echo "==> Disk after cleanup"
df -h / | tail -1
echo "==> Estimated freed: ~$(numfmt --to=iec-i --suffix=B "$FREED" 2>/dev/null || echo "${FREED} bytes")"
