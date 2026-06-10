# Deploy lên server (shopjfy6.com)

Quy trình chuẩn: **export DB local → push Git → pull trên server → migrate/build → import DB**.

## Yêu cầu

- SSH alias `ServerSand` (xem `~/.ssh/config`)
- Docker MySQL local (`shopefy-mysql-1`) hoặc `mysqldump` + file `.env`
- Trên server: `/www/wwwroot/shopjfy6.com`, PHP 8.4, Composer, Node/npm

## Cách nhanh (khuyên dùng)

```bash
# 1. Commit code local trước
git add -A && git status
git commit -m "your message"

# 2. Chạy deploy tự động
chmod +x scripts/deploy-server.sh
./scripts/deploy-server.sh
```

Script sẽ:

1. Export DB vào `../backups/shopefy_YYYYMMDD_HHMMSS.sql` (ngoài repo)
2. `git push origin main`
3. SSH: `git pull`, `composer install`, `npm ci && npm run build`, `php artisan migrate --force`, cache
4. Upload file SQL và import vào MySQL trên server (đọc `DB_*` từ `.env` server)

## Biến môi trường tùy chọn

```bash
SSH_HOST=ServerSand \
REMOTE_PATH=/www/wwwroot/shopjfy6.com \
GIT_BRANCH=main \
MYSQL_CONTAINER=shopefy-mysql-1 \
./scripts/deploy-server.sh
```

Bỏ qua bước:

| Biến | Ý nghĩa |
|------|---------|
| `SKIP_GIT_PUSH=1` | Không push Git |
| `SKIP_DB_EXPORT=1` | Không export DB local |
| `SKIP_DB_IMPORT=1` | Không import DB lên server (chỉ code + migrate) |

## Thủ công từng bước

### 1. Export DB local

```bash
mkdir -p ../backups
docker exec shopefy-mysql-1 mysqldump -ushopefy -pshopefy \
  --single-transaction --set-gtid-purged=OFF shopefy \
  | sed '/^mysqldump:/d' > ../backups/shopefy_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Push Git

```bash
git push -u origin main
```

### 3. Pull & build trên server

```bash
ssh ServerSand
cd /www/wwwroot/shopjfy6.com
git -c safe.directory=/www/wwwroot/shopjfy6.com pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www:www storage bootstrap/cache
```

### 4. Import DB lên server

```bash
# Từ máy local
scp ../backups/shopefy_YYYYMMDD_HHMMSS.sql ServerSand:/tmp/import.sql

# Trên server (dùng DB trong .env)
ssh ServerSand
cd /www/wwwroot/shopjfy6.com
source .env  # hoặc đọc tay DB_DATABASE, DB_USERNAME, DB_PASSWORD
mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < /tmp/import.sql
rm /tmp/import.sql
```

## Thông tin server

| Mục | Giá trị |
|-----|---------|
| Domain | https://shopjfy6.com |
| Path | `/www/wwwroot/shopjfy6.com` |
| SSH | `Host ServerSand` → `14.225.253.8` |
| DB | Xem `info.md` (local, không commit) |

## Cron (server)

```cron
* * * * * cd /www/wwwroot/shopjfy6.com && php artisan schedule:run >> /dev/null 2>&1
```

## Kiểm tra sau deploy

```bash
curl -sI https://shopjfy6.com/up | head -1
curl -sI https://shopjfy6.com/admin/login | head -1
```

## Lưu ý

- File `.env`, `info.md`, `*.sql` **không** đưa lên Git.
- Import DB sẽ **không ghi đè** dữ liệu production — chỉ chạy khi cố ý đồng bộ từ local.
- Nếu chỉ cập nhật code: `SKIP_DB_IMPORT=1 ./scripts/deploy-server.sh`
