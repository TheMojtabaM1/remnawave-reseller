#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
#  USVSIR — Remnawave Reseller (Agent) Management Panel
#  One-line installer (interactive, idempotent).
#
#    bash <(curl -fsSL https://raw.githubusercontent.com/TheMojtabam/USVSIR/main/install.sh)
#
#  Nothing is hardcoded: panel URL, API token, domain, owner creds and DB
#  settings are all entered below.
# ─────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO_URL="https://github.com/TheMojtabam/USVSIR.git"
APP_DIR="/opt/usvsir"
BRANCH="main"

c_green="\033[0;32m"; c_red="\033[0;31m"; c_yellow="\033[0;33m"; c_blue="\033[0;36m"; c_off="\033[0m"
info()  { echo -e "${c_blue}▶ $*${c_off}"; }
ok()    { echo -e "${c_green}✔ $*${c_off}"; }
warn()  { echo -e "${c_yellow}! $*${c_off}"; }
err()   { echo -e "${c_red}✘ $*${c_off}"; }
die()   { err "$*"; exit 1; }

[ "$(id -u)" -eq 0 ] || die "این اسکریپت باید با کاربر root اجرا شود (sudo)."

# ── OS detection ─────────────────────────────────────────────────────
. /etc/os-release 2>/dev/null || die "سیستم‌عامل پشتیبانی‌نشده."
case "${ID:-}" in
  ubuntu|debian) ;;
  *) [[ "${ID_LIKE:-}" == *debian* ]] || die "فقط Ubuntu/Debian پشتیبانی می‌شود." ;;
esac
ok "سیستم‌عامل: ${PRETTY_NAME:-$ID}"

# ── Prompts ──────────────────────────────────────────────────────────
echo; info "تنظیمات نصب را وارد کنید:"

read -rp "آدرس پنل Remnawave (بدون /api، مثال https://panel.example.com): " RW_BASE_URL
RW_BASE_URL="${RW_BASE_URL%/}"; RW_BASE_URL="${RW_BASE_URL%/api}"
[ -n "$RW_BASE_URL" ] || die "آدرس پنل الزامی است."

read -rp "توکن API پنل Remnawave: " RW_API_TOKEN
[ -n "$RW_API_TOKEN" ] || die "توکن API الزامی است."

info "بررسی اتصال به Remnawave..."
HTTP=$(curl -s -o /tmp/usvsir_squads.json -w "%{http_code}" -m 20 \
  -H "Authorization: Bearer ${RW_API_TOKEN}" "${RW_BASE_URL}/api/internal-squads" || echo "000")
if [ "$HTTP" != "200" ]; then
  die "اتصال/احراز هویت ناموفق بود (HTTP $HTTP). آدرس و توکن را بررسی کنید."
fi
ok "اتصال برقرار شد. Squadهای موجود:"
if command -v jq >/dev/null 2>&1; then
  jq -r '.response.internalSquads[]? | "  - \(.name)  [\(.uuid)]"' /tmp/usvsir_squads.json 2>/dev/null || true
else
  grep -oE '"name":"[^"]*"' /tmp/usvsir_squads.json | sed 's/"name":/  - /; s/"//g' || true
fi
warn "انتخاب Squadهای مجاز برای هر پلن/نماینده، داخل خود پنل انجام می‌شود."

read -rp "دامنه/زیر‌دامنه پنل (برای HTTPS، مثال agent.example.com): " APP_DOMAIN
[ -n "$APP_DOMAIN" ] || die "دامنه الزامی است."

read -rp "نام کاربری مدیر (owner): " OWNER_USER
[ -n "$OWNER_USER" ] || die "نام کاربری مدیر الزامی است."
read -rsp "رمز عبور مدیر: " OWNER_PASS; echo
[ -n "$OWNER_PASS" ] || die "رمز عبور مدیر الزامی است."

echo; echo "پایگاه‌داده:"
echo "  1) نصب MariaDB محلی و ساخت خودکار دیتابیس (پیشنهادی)"
echo "  2) استفاده از دیتابیس موجود"
read -rp "انتخاب [1/2]: " DB_CHOICE
DB_CHOICE="${DB_CHOICE:-1}"

if [ "$DB_CHOICE" = "2" ]; then
  read -rp "DB host [127.0.0.1]: " DB_HOST; DB_HOST="${DB_HOST:-127.0.0.1}"
  read -rp "DB port [3306]: " DB_PORT; DB_PORT="${DB_PORT:-3306}"
  read -rp "DB name [usvsir]: " DB_NAME; DB_NAME="${DB_NAME:-usvsir}"
  read -rp "DB user [usvsir]: " DB_USER; DB_USER="${DB_USER:-usvsir}"
  read -rsp "DB password: " DB_PASS; echo
else
  DB_HOST="127.0.0.1"; DB_PORT="3306"; DB_NAME="usvsir"; DB_USER="usvsir"
  DB_PASS="$(head -c 18 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
fi

# ── Install dependencies ─────────────────────────────────────────────
info "نصب پیش‌نیازها..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq curl git unzip ca-certificates apt-transport-https gnupg lsb-release jq >/dev/null

# PHP (FPM) + extensions
if ! command -v php >/dev/null 2>&1; then
  apt-get install -y -qq php-fpm php-cli php-mysql php-mbstring php-curl php-xml php-zip php-gd php-bcmath php-intl >/dev/null
else
  apt-get install -y -qq php-fpm php-cli php-mysql php-mbstring php-curl php-xml php-zip php-gd php-bcmath php-intl >/dev/null || true
fi
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"
ok "PHP ${PHP_VER} نصب شد."

# Composer
if ! command -v composer >/dev/null 2>&1; then
  info "نصب Composer..."
  php -r "copy('https://getcomposer.org/installer','/tmp/composer-setup.php');"
  php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi
ok "Composer آماده است."

# MariaDB (local option)
if [ "$DB_CHOICE" != "2" ]; then
  if ! command -v mariadb >/dev/null 2>&1 && ! command -v mysql >/dev/null 2>&1; then
    info "نصب MariaDB..."
    apt-get install -y -qq mariadb-server >/dev/null
  fi
  systemctl enable --now mariadb >/dev/null 2>&1 || systemctl enable --now mysql >/dev/null 2>&1 || true
fi

# Caddy
if ! command -v caddy >/dev/null 2>&1; then
  info "نصب Caddy..."
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' > /etc/apt/sources.list.d/caddy-stable.list
  apt-get update -qq
  apt-get install -y -qq caddy >/dev/null
fi
ok "Caddy آماده است."

# ── Fetch / update the app ───────────────────────────────────────────
if [ -d "$APP_DIR/.git" ]; then
  info "به‌روزرسانی مخزن موجود..."
  git -C "$APP_DIR" fetch --depth 1 origin "$BRANCH"
  git -C "$APP_DIR" reset --hard "origin/$BRANCH"
else
  info "دریافت کد در $APP_DIR ..."
  rm -rf "$APP_DIR"
  git clone --depth 1 -b "$BRANCH" "$REPO_URL" "$APP_DIR"
fi

info "نصب وابستگی‌های Composer..."
cd "$APP_DIR"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

# ── Database setup ───────────────────────────────────────────────────
if [ "$DB_CHOICE" != "2" ]; then
  info "ساخت دیتابیس و کاربر..."
  MYSQL_CMD="mysql"; command -v mariadb >/dev/null 2>&1 && MYSQL_CMD="mariadb"
  $MYSQL_CMD <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  DB_HOST="localhost"
  ok "دیتابیس ${DB_NAME} آماده شد."
fi

# ── Write .env ───────────────────────────────────────────────────────
info "نوشتن .env ..."
APP_KEY="$(head -c 32 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 40)"
cat > "$APP_DIR/.env" <<ENV
APP_NAME="USVSIR Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${APP_DOMAIN}
APP_KEY=${APP_KEY}
APP_TIMEZONE=Asia/Tehran

DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

RW_BASE_URL=${RW_BASE_URL}
RW_API_TOKEN=${RW_API_TOKEN}
RW_TIMEOUT=30
RW_EXTRA_HEADERS=

DEFAULT_TRAFFIC_STRATEGY=NO_RESET
CLEANUP_GRACE_DAYS=3
LOW_BALANCE_THRESHOLD=50000
BACKUP_KEEP=14
ENV
chmod 600 "$APP_DIR/.env"

# ── Migrate + seed ───────────────────────────────────────────────────
info "اجرای مهاجرت‌ها..."
php "$APP_DIR/database/migrate.php"
info "ساخت حساب مدیر..."
php "$APP_DIR/database/seed.php" "$OWNER_USER" "$OWNER_PASS"

# ── Permissions ──────────────────────────────────────────────────────
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/backups"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/backups"

# ── Caddy vhost ──────────────────────────────────────────────────────
info "پیکربندی Caddy..."
mkdir -p /var/log/caddy
cat > /etc/caddy/Caddyfile <<CADDY
${APP_DOMAIN} {
    root * ${APP_DIR}/public
    encode gzip
    php_fastcgi unix/${FPM_SOCK}
    file_server
    @forbidden {
        path /.env* /storage/* /src/* /database/* /migrations/* /cron/* /vendor/* /backups/*
    }
    respond @forbidden 404
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        Referrer-Policy no-referrer
        -Server
    }
    log { output file /var/log/caddy/usvsir.log }
}
CADDY
systemctl enable --now php${PHP_VER}-fpm >/dev/null 2>&1 || true
systemctl restart php${PHP_VER}-fpm || true
systemctl enable --now caddy >/dev/null 2>&1 || true
systemctl reload caddy 2>/dev/null || systemctl restart caddy || true

# ── Cron jobs ────────────────────────────────────────────────────────
info "نصب کران‌جاب‌ها..."
CRON_FILE="/etc/cron.d/usvsir"
cat > "$CRON_FILE" <<CRON
# USVSIR scheduled jobs (UTC)
*/5 * * * * www-data php ${APP_DIR}/cron/sync.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
*/10 * * * * www-data php ${APP_DIR}/cron/autosuspend.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
30 3 * * * www-data php ${APP_DIR}/cron/cleanup.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
0 4 * * * www-data php ${APP_DIR}/cron/backup.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
0 1 1 * * www-data php ${APP_DIR}/cron/statements.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
CRON
chmod 644 "$CRON_FILE"
systemctl restart cron 2>/dev/null || systemctl restart crond 2>/dev/null || true

# ── Done ─────────────────────────────────────────────────────────────
echo
ok "نصب کامل شد!"
echo -e "${c_green}──────────────────────────────────────────────${c_off}"
echo -e " آدرس پنل : ${c_blue}https://${APP_DOMAIN}${c_off}"
echo -e " مدیر     : ${OWNER_USER}"
echo -e " مسیر نصب : ${APP_DIR}"
if [ "$DB_CHOICE" != "2" ]; then
  echo -e " DB pass  : ${DB_PASS}  ${c_yellow}(در .env ذخیره شد)${c_off}"
fi
echo -e "${c_green}──────────────────────────────────────────────${c_off}"
warn "اگر DNS دامنه به این سرور اشاره کند، Caddy گواهی HTTPS را خودکار صادر می‌کند."
