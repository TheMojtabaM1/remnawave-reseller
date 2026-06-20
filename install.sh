#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
#  Remnawave Reseller (Agent) Management Panel
#  One-line installer (interactive, idempotent).
#
#    bash <(curl -fsSL https://raw.githubusercontent.com/TheMojtabaM1/remnawave-reseller/main/install.sh)
#
#  Nothing is hardcoded: panel URL, API token, domain, owner creds and DB
#  settings are all entered below.
# ─────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO_URL="https://github.com/TheMojtabaM1/remnawave-reseller.git"
APP_DIR="/opt/remnawave-reseller"
BRANCH="main"
STATE_DIR="/etc/remnawave-reseller"
STATE_FILE="${STATE_DIR}/port80.state"   # records what we stop on :80 (for renewal)

c_green="\033[0;32m"; c_red="\033[0;31m"; c_yellow="\033[0;33m"; c_blue="\033[0;36m"; c_off="\033[0m"
info()  { echo -e "${c_blue}▶ $*${c_off}"; }
ok()    { echo -e "${c_green}✔ $*${c_off}"; }
warn()  { echo -e "${c_yellow}! $*${c_off}"; }
err()   { echo -e "${c_red}✘ $*${c_off}"; }
die()   { err "$*"; exit 1; }

# ── Port helpers ─────────────────────────────────────────────────────
valid_port() { [[ "$1" =~ ^[0-9]+$ ]] && [ "$1" -ge 1 ] && [ "$1" -le 65535 ]; }

# Is anything listening on TCP port $1?
port_in_use() {
  ss -ltnH "sport = :$1" 2>/dev/null | grep -q . && return 0
  # Fallback for older `ss` without -H (the header line has no "LISTEN").
  ss -ltn "sport = :$1" 2>/dev/null | grep -q 'LISTEN'
}

# Wait until nothing listens on port $1 (max ~10s).
wait_port_free() {
  local p="$1" i=0
  while [ "$i" -lt 20 ]; do
    port_in_use "$p" || return 0
    sleep 0.5; i=$((i + 1))
  done
  return 0
}

# Detect & temporarily stop whatever holds port 80, recording the action to
# STATE_FILE so it can be restored (here and by the weekly renewal job).
free_port80() {
  mkdir -p "$STATE_DIR"
  : > "$STATE_FILE"

  if ! port_in_use 80; then
    info "پورت ۸۰ آزاد است."
    return 0
  fi

  # 1) A Docker container publishing host port 80 (most likely Remnawave)?
  if command -v docker >/dev/null 2>&1; then
    local cids cid cname
    cids=$(docker ps --format '{{.ID}} {{.Ports}}' 2>/dev/null \
           | awk '/(0\.0\.0\.0|::|[0-9.]+):80->/{print $1}' | sort -u || true)
    if [ -n "$cids" ]; then
      for cid in $cids; do
        cname=$(docker inspect -f '{{.Name}}' "$cid" 2>/dev/null | sed 's#^/##' || true)
        warn "توقف موقت کانتینر داکر روی پورت ۸۰: ${cname:-$cid}"
        echo "docker ${cid}" >> "$STATE_FILE"
        docker stop "$cid" >/dev/null 2>&1 || true
      done
      wait_port_free 80
      return 0
    fi
  fi

  # 2) A host service (systemd) holding :80?
  local pid pname unit
  pid=$(ss -ltnpH "sport = :80" 2>/dev/null | grep -oE 'pid=[0-9]+' | head -1 | cut -d= -f2 || true)
  if [ -n "${pid:-}" ]; then
    pname=$(ps -o comm= -p "$pid" 2>/dev/null | tr -d ' ' || true)
    unit=$(systemctl status "$pid" 2>/dev/null | grep -oE '[A-Za-z0-9@_.\-]+\.service' | head -1 || true)
    if [ -z "${unit:-}" ]; then
      case "$pname" in
        nginx|apache2|httpd|haproxy|lighttpd|caddy) unit="${pname}.service" ;;
      esac
    fi
    if [ -n "${unit:-}" ]; then
      warn "توقف موقت سرویس روی پورت ۸۰: ${unit}"
      echo "systemd ${unit}" >> "$STATE_FILE"
      systemctl stop "$unit" >/dev/null 2>&1 || true
      wait_port_free 80
      return 0
    fi
    warn "سرویس ناشناخته روی پورت ۸۰ (PID ${pid}, ${pname:-?}) موقتاً متوقف می‌شود (به‌صورت خودکار قابل بازگردانی نیست)."
    echo "pid ${pid}" >> "$STATE_FILE"
    kill "$pid" 2>/dev/null || true
    wait_port_free 80
    return 0
  fi

  warn "تشخیص ندادم چه چیزی پورت ۸۰ را گرفته؛ ادامه می‌دهم."
  return 0
}

# Restore whatever free_port80 stopped.
restore_port80() {
  [ -f "$STATE_FILE" ] || return 0
  local kind target
  while read -r kind target; do
    [ -n "${kind:-}" ] || continue
    case "$kind" in
      docker)  docker start "$target"   >/dev/null 2>&1 || true ;;
      systemd) systemctl start "$target" >/dev/null 2>&1 || true ;;
      pid)     : ;;  # original process was killed; cannot auto-restart
    esac
  done < "$STATE_FILE"
  return 0
}

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
HTTP=$(curl -s -o /tmp/rwr_squads.json -w "%{http_code}" -m 20 \
  -H "Authorization: Bearer ${RW_API_TOKEN}" "${RW_BASE_URL}/api/internal-squads" || echo "000")
if [ "$HTTP" != "200" ]; then
  die "اتصال/احراز هویت ناموفق بود (HTTP $HTTP). آدرس و توکن را بررسی کنید."
fi
ok "اتصال برقرار شد. Squadهای موجود:"
if command -v jq >/dev/null 2>&1; then
  jq -r '.response.internalSquads[]? | "  - \(.name)  [\(.uuid)]"' /tmp/rwr_squads.json 2>/dev/null || true
else
  grep -oE '"name":"[^"]*"' /tmp/rwr_squads.json | sed 's/"name":/  - /; s/"//g' || true
fi
warn "انتخاب Squadهای مجاز برای هر پلن/نماینده، داخل خود پنل انجام می‌شود."

read -rp "دامنه/زیر‌دامنه پنل (برای HTTPS، مثال agent.example.com): " APP_DOMAIN
[ -n "$APP_DOMAIN" ] || die "دامنه الزامی است."

echo; info "پورت‌های پنل (۴۴۳ و ۸۰ روی این سرور اشغال‌اند؛ پورت‌های دلخواه و آزاد انتخاب کنید):"
read -rp "پورت پنل ادمین [8443]: " ADMIN_PORT;       ADMIN_PORT="${ADMIN_PORT:-8443}"
read -rp "پورت پنل نماینده [9443]: " RESELLER_PORT;   RESELLER_PORT="${RESELLER_PORT:-9443}"
valid_port "$ADMIN_PORT"    || die "پورت پنل ادمین نامعتبر است (۱ تا ۶۵۵۳۵)."
valid_port "$RESELLER_PORT" || die "پورت پنل نماینده نامعتبر است (۱ تا ۶۵۵۳۵)."
[ "$ADMIN_PORT" != "$RESELLER_PORT" ] || die "پورت ادمین و نماینده نباید یکسان باشند."
for p in "$ADMIN_PORT" "$RESELLER_PORT"; do
  case "$p" in 80|443) die "پورت $p روی این سرور اشغال است؛ پورت دیگری انتخاب کنید." ;; esac
  if port_in_use "$p"; then
    warn "به‌نظر می‌رسد پورت $p هم‌اکنون در حال استفاده است؛ ممکن است Caddy نتواند روی آن گوش دهد."
  fi
done

read -rp "ایمیل برای گواهی Let's Encrypt (اختیاری، Enter برای رد شدن): " LE_EMAIL
LE_EMAIL="$(echo "${LE_EMAIL:-}" | tr -d ' ')"

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
  read -rp "DB name [remnawave_reseller]: " DB_NAME; DB_NAME="${DB_NAME:-remnawave_reseller}"
  read -rp "DB user [remnawave_reseller]: " DB_USER; DB_USER="${DB_USER:-remnawave_reseller}"
  read -rsp "DB password: " DB_PASS; echo
else
  DB_HOST="127.0.0.1"; DB_PORT="3306"; DB_NAME="remnawave_reseller"; DB_USER="remnawave_reseller"
  DB_PASS="$(head -c 18 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
fi

# ── Install dependencies ─────────────────────────────────────────────
info "نصب پیش‌نیازها..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq curl git unzip ca-certificates apt-transport-https gnupg lsb-release jq iproute2 openssl lsof >/dev/null

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
APP_NAME="Remnawave Reseller"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${APP_DOMAIN}:${ADMIN_PORT}
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
# php-fpm and the cron jobs run as www-data and must be able to read .env
# (DB credentials, RW token, …); root-owned 600 would make the web app connect
# to MySQL with no password and every page 500s.
chown www-data:www-data "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"

# ── Migrate + seed ───────────────────────────────────────────────────
info "اجرای مهاجرت‌ها..."
php "$APP_DIR/database/migrate.php"
info "ساخت حساب مدیر..."
php "$APP_DIR/database/seed.php" "$OWNER_USER" "$OWNER_PASS"

# ── Permissions ──────────────────────────────────────────────────────
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/backups"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/backups"

# ── TLS certificate (certbot --standalone) ───────────────────────────
# Both panels run on custom HTTPS ports. Caddy must NOT keep port 80 (Remnawave
# uses it), so we DON'T use Caddy's built-in ACME (which would hold :80 to answer
# future challenges). Instead certbot gets the cert in standalone mode — it needs
# :80 only for the brief HTTP-01 challenge — and Caddy then serves the panels with
# that cert supplied explicitly, never binding :80 itself.
info "نصب certbot..."
apt-get install -y -qq certbot >/dev/null 2>&1 || true
systemctl disable --now certbot.timer >/dev/null 2>&1 || true   # we renew on our own schedule

CERT_DIR="/etc/letsencrypt/live/${APP_DOMAIN}"
CADDY_CERT="/etc/caddy/certs/${APP_DOMAIN}.crt"
CADDY_KEY="/etc/caddy/certs/${APP_DOMAIN}.key"

# Deploy hook: copy each freshly issued/renewed cert to a Caddy-readable place
# (the live/ dir is root-only) and reload Caddy. certbot runs this on every renew.
DEPLOY_BIN="/usr/local/bin/remnawave-reseller-deploy-cert"
cat > "$DEPLOY_BIN" <<DEPLOY
#!/usr/bin/env bash
set -uo pipefail
DOMAIN="${APP_DOMAIN}"
SRC="\${RENEWED_LINEAGE:-/etc/letsencrypt/live/\${DOMAIN}}"
DEST="/etc/caddy/certs"
install -d -o caddy -g caddy -m 0750 "\$DEST" 2>/dev/null || mkdir -p "\$DEST"
cp -L "\$SRC/fullchain.pem" "\$DEST/\${DOMAIN}.crt" 2>/dev/null || exit 0
cp -L "\$SRC/privkey.pem"   "\$DEST/\${DOMAIN}.key" 2>/dev/null || exit 0
chown caddy:caddy "\$DEST/\${DOMAIN}.crt" "\$DEST/\${DOMAIN}.key" 2>/dev/null || true
chmod 0644 "\$DEST/\${DOMAIN}.crt"; chmod 0640 "\$DEST/\${DOMAIN}.key"
systemctl reload caddy 2>/dev/null || true
DEPLOY
chmod 755 "$DEPLOY_BIN"

CB_EMAIL_ARGS="--register-unsafely-without-email"
[ -n "${LE_EMAIL:-}" ] && CB_EMAIL_ARGS="-m ${LE_EMAIL}"

# Free port 80 → issue cert → hand port 80 straight back. The trap guarantees
# port 80 is restored even if certbot fails midway.
info "آزادسازی موقت پورت ۸۰ برای دریافت گواهی Let's Encrypt..."
trap 'restore_port80' EXIT
free_port80
certbot certonly --standalone --non-interactive --agree-tos $CB_EMAIL_ARGS \
  --preferred-challenges http --keep-until-expiring \
  --deploy-hook "$DEPLOY_BIN" -d "${APP_DOMAIN}" || true
info "بازگرداندن پورت ۸۰ به سرویس قبلی..."
restore_port80 || true
trap - EXIT

# Copy the cert into Caddy's dir now too (the deploy hook only fires on change).
[ -f "${CERT_DIR}/fullchain.pem" ] && RENEWED_LINEAGE="${CERT_DIR}" "$DEPLOY_BIN" || true

if [ -f "$CADDY_CERT" ] && [ -f "$CADDY_KEY" ]; then
  ok "گواهی Let's Encrypt آماده شد."
  TLS_LINE="tls ${CADDY_CERT} ${CADDY_KEY}"
else
  warn "دریافت گواهی Let's Encrypt ناموفق بود (رکورد DNS دامنه را به IP این سرور اشاره دهید و نصب را دوباره اجرا کنید). فعلاً گواهی داخلی موقت (self-signed) استفاده می‌شود."
  TLS_LINE="tls internal"
fi

# ── Caddy vhosts (admin + reseller panels on custom HTTPS ports) ─────
info "پیکربندی Caddy (پنل ادمین: ${ADMIN_PORT} | پنل نماینده: ${RESELLER_PORT})..."
# Caddy runs as the `caddy` user; it must own its log dir or it can't open the
# per-site log files and the service exits on startup.
mkdir -p /var/log/caddy
chown caddy:caddy /var/log/caddy 2>/dev/null || true

cat > /etc/caddy/Caddyfile <<CADDY
{
    # Custom HTTPS ports only. Caddy must never hold port 80 (Remnawave uses it):
    # the certificate is supplied explicitly (no ACME inside Caddy) and the
    # automatic HTTP->HTTPS redirects (which would open :80) are disabled.
    auto_https disable_redirects
}

# Shared site definition for both panels (same app, same certificate).
(panel) {
    root * ${APP_DIR}/public
    encode gzip
    php_fastcgi unix/${FPM_SOCK}
    file_server
    @forbidden {
        path /.env* /storage/* /src/* /database/* /migrations/* /cron/* /vendor/* /backups/*
    }
    respond @forbidden 404
    ${TLS_LINE}
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        Referrer-Policy no-referrer
        -Server
    }
}

https://${APP_DOMAIN}:${ADMIN_PORT} {
    import panel
    log {
        output file /var/log/caddy/remnawave-reseller-admin.log
    }
}

https://${APP_DOMAIN}:${RESELLER_PORT} {
    import panel
    log {
        output file /var/log/caddy/remnawave-reseller-reseller.log
    }
}
CADDY

# Open the panel ports in the firewall (best-effort, only if ufw is active).
if command -v ufw >/dev/null 2>&1 && ufw status 2>/dev/null | grep -q "Status: active"; then
  ufw allow "${ADMIN_PORT}/tcp"    >/dev/null 2>&1 || true
  ufw allow "${RESELLER_PORT}/tcp" >/dev/null 2>&1 || true
  ok "پورت‌های ${ADMIN_PORT} و ${RESELLER_PORT} در فایروال باز شدند."
fi

systemctl enable --now php${PHP_VER}-fpm >/dev/null 2>&1 || true
systemctl restart php${PHP_VER}-fpm || true
systemctl enable caddy >/dev/null 2>&1 || true
systemctl restart caddy || true

# ── Cron jobs ────────────────────────────────────────────────────────
info "نصب کران‌جاب‌ها..."
CRON_FILE="/etc/cron.d/remnawave-reseller"
cat > "$CRON_FILE" <<CRON
# Remnawave Reseller scheduled jobs (UTC)
*/5 * * * * www-data php ${APP_DIR}/cron/sync.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
*/10 * * * * www-data php ${APP_DIR}/cron/autosuspend.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
30 3 * * * www-data php ${APP_DIR}/cron/cleanup.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
0 4 * * * www-data php ${APP_DIR}/cron/backup.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
0 1 1 * * www-data php ${APP_DIR}/cron/statements.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
CRON
chmod 644 "$CRON_FILE"

# Certificate auto-renewal helper. 443 stays busy, so renewal also needs port
# 80 freed briefly — but only when the cert is actually close to expiring, so
# Remnawave's port 80 is left alone the rest of the time.
info "نصب اسکریپت تمدید خودکار گواهی..."
RENEW_BIN="/usr/local/bin/remnawave-reseller-renew-cert"
cat > "$RENEW_BIN" <<RENEW
#!/usr/bin/env bash
# Auto-generated by the Remnawave Reseller installer. Renews the Let's Encrypt
# certificate with certbot --standalone, briefly freeing port 80 for the HTTP-01
# challenge ONLY when the cert is close to expiring, then restoring port 80.
# certbot runs the deploy hook on success (copies the cert + reloads Caddy).
set -uo pipefail
STATE_FILE="${STATE_FILE}"
CRT="${CADDY_CERT}"

stop_port80() {
  [ -f "\$STATE_FILE" ] || return 0
  while read -r kind target; do
    [ -n "\${kind:-}" ] || continue
    case "\$kind" in
      docker)  docker stop "\$target"   >/dev/null 2>&1 || true ;;
      systemd) systemctl stop "\$target" >/dev/null 2>&1 || true ;;
    esac
  done < "\$STATE_FILE"
}
start_port80() {
  [ -f "\$STATE_FILE" ] || return 0
  while read -r kind target; do
    [ -n "\${kind:-}" ] || continue
    case "\$kind" in
      docker)  docker start "\$target"   >/dev/null 2>&1 || true ;;
      systemd) systemctl start "\$target" >/dev/null 2>&1 || true ;;
    esac
  done < "\$STATE_FILE"
}

# Don't touch port 80 unless the cert expires within 30 days.
if [ -f "\$CRT" ] && openssl x509 -checkend \$((30*86400)) -noout -in "\$CRT" >/dev/null 2>&1; then
  echo "[\$(date -u '+%F %T')] گواهی هنوز معتبر است؛ نیازی به تمدید نیست."
  exit 0
fi

echo "[\$(date -u '+%F %T')] تمدید گواهی: آزادسازی موقت پورت ۸۰..."
trap 'start_port80' EXIT
stop_port80
sleep 2
certbot renew --quiet --preferred-challenges http || true   # deploy hook copies cert + reloads Caddy
start_port80
trap - EXIT
echo "[\$(date -u '+%F %T')] تمدید گواهی انجام شد."
RENEW
chmod 755 "$RENEW_BIN"

cat > /etc/cron.d/remnawave-reseller-cert <<CRON
# Weekly Let's Encrypt renewal for the reseller panels (frees :80 only when due)
30 3 * * 0 root ${RENEW_BIN} >> ${APP_DIR}/storage/logs/cert-renew.log 2>&1
CRON
chmod 644 /etc/cron.d/remnawave-reseller-cert

systemctl restart cron 2>/dev/null || systemctl restart crond 2>/dev/null || true

# ── Done ─────────────────────────────────────────────────────────────
echo
ok "نصب کامل شد!"
echo -e "${c_green}──────────────────────────────────────────────${c_off}"
echo -e " پنل ادمین   : ${c_blue}https://${APP_DOMAIN}:${ADMIN_PORT}${c_off}"
echo -e " پنل نماینده : ${c_blue}https://${APP_DOMAIN}:${RESELLER_PORT}${c_off}"
echo -e " مدیر        : ${OWNER_USER}"
echo -e " مسیر نصب    : ${APP_DIR}"
if [ "$DB_CHOICE" != "2" ]; then
  echo -e " DB pass     : ${DB_PASS}  ${c_yellow}(در .env ذخیره شد)${c_off}"
fi
echo -e "${c_green}──────────────────────────────────────────────${c_off}"
warn "هر دو پنل از یک برنامه سرو می‌شوند ولی سشن‌ها per-port جدا هستند: آدرس پورت ادمین را برای خودتان و آدرس پورت نماینده را به نمایندگان بدهید."
if [ -f "$CADDY_CERT" ] && [ -f "$CADDY_KEY" ]; then
  warn "گواهی Let's Encrypt صادر شد. Caddy هرگز پورت ۸۰ را نگه نمی‌دارد؛ برای تمدید، پورت ۸۰ فقط هنگام نزدیک‌شدن گواهی به انقضا و تنها چند ثانیه آزاد می‌شود (کران هفتگی)."
else
  warn "گواهی Let's Encrypt گرفته نشد و فعلاً گواهی موقت داخلی فعال است. DNS دامنه «${APP_DOMAIN}» را به IP این سرور اشاره دهید و نصب را دوباره اجرا کنید."
fi
