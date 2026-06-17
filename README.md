# USVSIR — Remnawave Reseller (Agent) Management Panel

پنل مدیریت نمایندگان برای **Remnawave** — یک لایه‌ی نمایندگی/Reseller روی پنل Remnawave که از طریق REST API آن کار می‌کند. مالک (سوپر‌ادمین) نمایندگان را با محدودیت‌ها، دسترسی‌ها، کیف پول و قیمت‌گذاری مدیریت می‌کند؛ هر نماینده کانفیگ‌ها (=کاربران Remnawave) را در سقف خود می‌سازد و مدیریت می‌کند.

A reseller/agent management layer on top of an existing **Remnawave** panel, built entirely on its REST API. Remnawave is single‑admin and has no native sub‑admin system — USVSIR adds it. Owner (super‑admin) → Reseller (single level, no sub‑resellers).

---

## ⚡ نصب تک‌خطی / One‑line install

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/TheMojtabam/USVSIR/main/install.sh)
```

نصب‌کننده‌ی تعاملی موارد زیر را می‌پرسد (هیچ‌چیز هاردکد نشده): آدرس پنل Remnawave، توکن API (با یک فراخوانی واقعی اعتبارسنجی و لیست Squadها نمایش داده می‌شود)، دامنه (برای HTTPS خودکار Caddy)، نام‌کاربری/رمز مدیر، و پایگاه‌داده (نصب MariaDB محلی یا استفاده از دیتابیس موجود). سپس وابستگی‌ها را نصب، `.env` را نوشته، مهاجرت‌ها و seed را اجرا، Caddy و کران‌جاب‌ها را پیکربندی و سرویس‌ها را راه‌اندازی می‌کند. اسکریپت **idempotent** است و قابل اجرای مجدد.

---

## ✨ امکانات (فاز ۱) / Features

**پنل مالک (Owner):**
- مدیریت کامل نمایندگان: همه‌ی محدودیت‌ها، دسترسی‌ها و قیمت‌گذاری به‌ازای هر نماینده؛ فعال/تعلیق.
- کیف پول: افزایش/کاهش/تنظیم/صفرکردن موجودی با ثبت تراکنش و لاگ.
- پلن‌ها و قالب‌ها (CRUD)، قیمت‌گذاری پلکانی (Tiered) سراسری/پلنی.
- دریافت زنده‌ی Squadها از Remnawave و انتخاب آن‌ها.
- گزارش‌های مالی + نمودار (Chart.js) + خروجی Excel و صورتحساب PDF.
- عملیات گروهی، لاگ فعالیت، هشدارها، مانیتور کاربران آنلاین، سلامت نودها، برترین نمایندگان، پشتیبان‌گیری/بازیابی.

**پنل نماینده (Reseller):**
- ساخت کانفیگ از پلن/قالب/سفارشی (با اعمال محدودیت‌ها و کسر از کیف پول).
- مدیریت کانفیگ‌ها: جستجو/فیلتر/صفحه‌بندی، مصرف زنده، تمدید، فعال/غیرفعال، حذف با **بازگشت وجه حجمی**، بازتولید لینک اشتراک + QR.
- کیف پول و تاریخچه‌ی تراکنش‌ها.

**قانون بازگشت وجه (فقط حجم):**
```
unused_gb = max(0, volume_gb - used_bytes/1073741824)
refund    = floor(price_charged * unused_gb / volume_gb)
```
زمان/مدت باقی‌مانده در بازگشت وجه نقشی ندارد.

---

## 🧱 پشته‌ی فنی / Stack
PHP 8.2+، MySQL/MariaDB (PDO + prepared statements)، بدون فریم‌ورک سنگین (front controller + router کوچک، PSR‑4)، Tailwind + RTL فارسی + فونت Vazirmatn + Chart.js. مبالغ به تومان (عدد صحیح). زمان‌ها UTC ذخیره و به وقت تهران + تقویم شمسی نمایش داده می‌شوند. امنیت: CSRF، Argon2id، session سخت‌شده، محدودسازی نرخ ورود، و **اعمال سمت سرور همه‌ی محدودیت‌ها/دسترسی‌ها**.

کتابخانه‌ها: `guzzlehttp/guzzle`, `vlucas/phpdotenv`, `endroid/qr-code`, `phpoffice/phpspreadsheet`, `mpdf/mpdf`, `morilog/jalali`.

---

## 🔌 یکپارچگی با Remnawave
کلاس واحد `App\Services\RemnawaveClient` همه‌ی فراخوانی‌ها را به‌صورت **دفاعی** پوشش می‌دهد: پاکت `{"response": {...}}` را باز می‌کند، خطاها را با بدنه‌ی خام در `storage/logs/remnawave.log` لاگ می‌کند، و مسیر همه‌ی اندپوینت‌ها در یک نقشه‌ی متمرکز (`RemnawaveClient::EP`) قرار دارد تا در صورت تفاوت نسخه‌ی پنل به‌سادگی اصلاح شود.

- هویت: هر کاربر ساخته‌شده تگ `RSL_<reseller_id>` (حروف بزرگ، مطابق محدودیت Remnawave) و یوزرنیم `<prefix>_<random8>` می‌گیرد.
- حجم: `trafficLimitBytes = gb * 1073741824`.
- اعتبارسنجی توکن هنگام نصب با یک فراخوانی واقعی انجام می‌شود (Swagger ممکن است در پنل غیرفعال باشد).

---

## 🛠️ نصب دستی / Manual install
```bash
sudo apt install -y php-fpm php-cli php-mysql php-mbstring php-curl php-xml php-zip php-gd php-bcmath php-intl mariadb-server caddy git composer
git clone -b main https://github.com/TheMojtabam/USVSIR.git /opt/usvsir
cd /opt/usvsir && composer install --no-dev --optimize-autoloader
cp .env.example .env      # سپس مقادیر DB و RW_* و APP_KEY را تنظیم کنید
php database/migrate.php
php database/seed.php <owner_user> <owner_pass>
sudo chown -R www-data:www-data storage backups
# vhost را از روی Caddyfile.example بسازید و دامنه را تنظیم کنید
```

### کران‌جاب‌ها / Cron (UTC)
```
*/5  * * * *  php /opt/usvsir/cron/sync.php          # همگام‌سازی مصرف/انقضا/آنلاین
*/10 * * * *  php /opt/usvsir/cron/autosuspend.php   # تعلیق خودکار بدهکاران
30 3 * * *    php /opt/usvsir/cron/cleanup.php       # حذف منقضی‌های پس از مهلت
0 4 * * *     php /opt/usvsir/cron/backup.php        # پشتیبان روزانه + چرخش
0 1 1 * *     php /opt/usvsir/cron/statements.php    # صورتحساب ماهانه
```

---

## ⚙️ پیکربندی / Configuration
همه‌ی مقادیر حساس در `.env` هستند (نمونه: `.env.example`). تنظیمات قابل‌ویرایش در UI (`settings`): نام برنامه، روزهای مهلت پاک‌سازی، آستانه‌ی هشدار موجودی کم، استراتژی ترافیک پیش‌فرض، آستانه‌ی جهش ترافیک.

> **هشدار امنیتی:** فایل `.env` هرگز نباید commit شود (در `.gitignore` است). توکن API و رمزها را محرمانه نگه دارید.

---

## 🚫 خارج از محدوده
این پنل فقط API پنل Remnawave را مصرف می‌کند و به هسته‌ی Xray یا داخلیات پروتکل‌ها دست نمی‌زند (مدیریت هسته/پروتکل بر عهده‌ی خود Remnawave است).

## 🗺️ نقشه‌ی راه (پیاده‌سازی‌نشده)
ربات تلگرام، شارژ آنلاین/درگاه پرداخت، کدهای هدیه/تخفیف، کانفیگ تست خودسرویس، برندینگ اختصاصی، 2FA، ادمین‌های پشتیبان، و چند‌پنلی — قلاب‌های دیتابیس/معماری برای این موارد در نظر گرفته شده‌اند.

---

MIT License.
