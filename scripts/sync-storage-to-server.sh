#!/usr/bin/env bash
# Sync uploaded media (storage/app/public + public/uploads) to production.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SSH_HOST="${SSH_HOST:-ServerSand}"
REMOTE_PATH="${REMOTE_PATH:-/www/wwwroot/shopjfy6.com}"

log() { printf '\n[%s] %s\n' "$(date +%H:%M:%S)" "$*"; }

log "Pack local storage/app/public ($(du -sh "$ROOT/storage/app/public" | awk '{print $1}'))"
tar czf /tmp/shopefy-storage-public.tgz -C "$ROOT/storage/app/public" .

log "Pack local public/uploads ($(du -sh "$ROOT/public/uploads" | awk '{print $1}'))"
tar czf /tmp/shopefy-public-uploads.tgz -C "$ROOT/public/uploads" .

log "Upload archives → $SSH_HOST"
scp -o BatchMode=yes /tmp/shopefy-storage-public.tgz /tmp/shopefy-public-uploads.tgz "$SSH_HOST:/tmp/"

log "Extract on server + fix permissions"
ssh -o BatchMode=yes "$SSH_HOST" "REMOTE_PATH='$REMOTE_PATH' bash -s" <<'REMOTE'
set -euo pipefail
cd "$REMOTE_PATH"
mkdir -p storage/app/public public/uploads
tar xzf /tmp/shopefy-storage-public.tgz -C storage/app/public
tar xzf /tmp/shopefy-public-uploads.tgz -C public/uploads
chown -R www:www storage/app/public public/uploads
chmod -R ug+rwx storage/app/public public/uploads
rm -f /tmp/shopefy-storage-public.tgz /tmp/shopefy-public-uploads.tgz
echo "Storage synced at $REMOTE_PATH"
REMOTE

rm -f /tmp/shopefy-storage-public.tgz /tmp/shopefy-public-uploads.tgz
log "Done"
