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
5. `php artisan db:ensure-ready` — đảm bảo admin/member và role tồn tại sau import
6. `php artisan shops:sync-roles` — đồng bộ role `shop` theo bảng shops

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
php artisan db:ensure-ready --no-interaction
php artisan shops:sync-roles --no-interaction
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
- **Import ghi đè toàn bộ DB production bằng bản export local** — mọi user/order trên server bị thay thế.
- Trước khi deploy, đảm bảo DB local Docker đã seed (`php artisan db:ensure-ready` hoặc `db:seed`) để export không rỗng/thiếu role.
- Nếu chỉ cập nhật code: `SKIP_DB_IMPORT=1 ./scripts/deploy-server.sh`

## Sự cố đã gặp: Admin không đăng nhập được sau deploy

**Ngày:** 2026-06-10 · **Site:** https://shopjfy6.com/admin/login

### Triệu chứng

- Tài khoản demo `admin@shopi.com` / `Abc@123123` không đăng nhập được ngay sau khi chạy `./scripts/deploy-server.sh`.
- Site vẫn trả HTTP 200 (`/up`, trang login hiển thị bình thường).

### Nguyên nhân

1. Script deploy **import dump MySQL local lên production**, ghi đè toàn bộ bảng `users`, `roles`, …
2. DB local (Docker `shopefy-mysql-1`) lúc đó **0 user** hoặc thiếu role `admin` — chỉ có role `shop`.
3. Sau import, production không còn admin → login thất bại.
4. Trước khi sửa, script **không** chạy bước khôi phục tài khoản sau import.

### Khắc phục tức thì (trên server)

```bash
ssh ServerSand
cd /www/wwwroot/shopjfy6.com
php artisan db:seed --class=Database\\Seeders\\RoleAndAdminSeeder --force
# hoặc
php artisan db:ensure-ready --no-interaction
```

Kiểm tra:

```bash
php artisan tinker --execute="echo App\Models\User::role('admin')->count();"
```

### Khắc phục lâu dài (trong repo)

| Thay đổi | Mục đích |
|----------|----------|
| `scripts/deploy-server.sh` — sau import chạy `db:ensure-ready` + `shops:sync-roles` | Luôn có admin/member và role shop sau mỗi deploy |
| `RoleAndAdminSeeder` — luôn gán lại mật khẩu `Abc@123123` cho admin | Mật khẩu demo ổn định sau seed idempotent |
| Seed DB local trước khi export | Dump không còn rỗng/thiếu role |

### Phòng tránh

- **Trước deploy:** `docker compose exec app php artisan db:ensure-ready` (hoặc seed local).
- **Deploy chỉ code** (giữ data production): `SKIP_DB_IMPORT=1 ./scripts/deploy-server.sh`.
- **Sau deploy:** thử login admin + kiểm tra `/up` (mục *Kiểm tra sau deploy*).
b