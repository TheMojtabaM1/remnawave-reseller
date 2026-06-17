<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

final class ResellerController
{
    private const PERMS = [
        'can_create_config', 'can_edit_config', 'can_delete_config', 'can_renew',
        'can_regenerate_subscription', 'can_create_custom', 'can_use_trial',
    ];

    public function index(): void
    {
        $resellers = Db::all(
            'SELECT r.*,
                (SELECT COUNT(*) FROM configs c WHERE c.reseller_id=r.id AND c.status<>"deleted") AS configs_count
             FROM resellers r ORDER BY r.created_at DESC'
        );
        View::render('owner/resellers/index', ['title' => 'نمایندگان', 'resellers' => $resellers]);
    }

    public function create(): void
    {
        View::render('owner/resellers/form', [
            'title' => 'افزودن نماینده',
            'reseller' => null,
            'squads' => $this->squads(),
            'perms' => self::PERMS,
        ]);
    }

    public function store(Request $request): void
    {
        $username = trim((string) $request->post('username'));
        $password = (string) $request->post('password');
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', (string) $request->post('prefix')) ?: '';

        if ($username === '' || $password === '' || $prefix === '') {
            flash('error', 'نام کاربری، رمز عبور و پیشوند الزامی هستند.');
            flash_old($request->all());
            Response::redirect('/owner/resellers/create');
        }
        if (Db::one('SELECT id FROM resellers WHERE username = :u', [':u' => $username])) {
            flash('error', 'این نام کاربری قبلاً استفاده شده است.');
            flash_old($request->all());
            Response::redirect('/owner/resellers/create');
        }
        if (Db::one('SELECT id FROM resellers WHERE prefix = :p', [':p' => $prefix])) {
            flash('error', 'این پیشوند قبلاً استفاده شده است.');
            flash_old($request->all());
            Response::redirect('/owner/resellers/create');
        }

        $f = $this->fields($request);
        $id = Db::insert(
            'INSERT INTO resellers
                (username, password_hash, display_name, prefix, telegram_id, notes, status, access_expires_at,
                 balance, allow_debt, debt_limit, max_users, max_users_per_day, min_volume_gb, max_volume_gb,
                 min_days, max_days, max_total_traffic_gb, hwid_device_limit, allowed_squads, permissions,
                 price_per_gb, price_per_day, discount_percent, created_at)
             VALUES (:username,:ph,:dn,:prefix,:tg,:notes,:status,:exp,:bal,:adebt,:dlimit,:mu,:mupd,:minv,:maxv,
                     :mind,:maxd,:pool,:hwid,:squads,:perms,:ppg,:ppd,:disc,UTC_TIMESTAMP())',
            array_merge($f, [
                ':username' => $username,
                ':ph' => password_hash($password, PASSWORD_ARGON2ID),
                ':prefix' => $prefix,
            ])
        );
        AuditLogger::log('reseller.create', 'reseller', $id, ['username' => $username]);
        clear_old();
        flash('success', 'نماینده با موفقیت ایجاد شد.');
        Response::redirect('/owner/resellers/' . $id);
    }

    public function show(Request $request, array $args): void
    {
        $r = $this->find((int) $args['id']);
        $configs = Db::all('SELECT * FROM configs WHERE reseller_id=:id AND status<>"deleted" ORDER BY created_at DESC LIMIT 100', [':id' => $r['id']]);
        $txs = Db::all('SELECT * FROM transactions WHERE reseller_id=:id ORDER BY id DESC LIMIT 50', [':id' => $r['id']]);
        $stats = [
            'active' => (int) Db::scalar('SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status="active"', [':id' => $r['id']]),
            'sales' => (int) Db::scalar("SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE reseller_id=:id AND type='charge'", [':id' => $r['id']]),
        ];
        View::render('owner/resellers/show', ['title' => 'نماینده: ' . ($r['display_name'] ?: $r['username']), 'r' => $r, 'configs' => $configs, 'txs' => $txs, 'stats' => $stats, 'squads' => $this->squads()]);
    }

    public function edit(Request $request, array $args): void
    {
        $r = $this->find((int) $args['id']);
        View::render('owner/resellers/form', [
            'title' => 'ویرایش نماینده',
            'reseller' => $r,
            'squads' => $this->squads(),
            'perms' => self::PERMS,
        ]);
    }

    public function update(Request $request, array $args): void
    {
        $r = $this->find((int) $args['id']);
        $f = $this->fields($request);

        $sql = 'UPDATE resellers SET display_name=:dn, telegram_id=:tg, notes=:notes, status=:status,
                    access_expires_at=:exp, allow_debt=:adebt, debt_limit=:dlimit, max_users=:mu,
                    max_users_per_day=:mupd, min_volume_gb=:minv, max_volume_gb=:maxv, min_days=:mind,
                    max_days=:maxd, max_total_traffic_gb=:pool, hwid_device_limit=:hwid, allowed_squads=:squads,
                    permissions=:perms, price_per_gb=:ppg, price_per_day=:ppd, discount_percent=:disc';
        $params = $f;
        unset($params[':bal']); // balance only changes through wallet
        $params[':id'] = $r['id'];

        $newPass = (string) $request->post('password');
        if ($newPass !== '') {
            $sql .= ', password_hash=:ph';
            $params[':ph'] = password_hash($newPass, PASSWORD_ARGON2ID);
        }
        $sql .= ' WHERE id=:id';
        Db::exec($sql, $params);

        AuditLogger::log('reseller.update', 'reseller', (int) $r['id']);
        flash('success', 'تغییرات ذخیره شد.');
        Response::redirect('/owner/resellers/' . $r['id']);
    }

    public function toggleStatus(Request $request, array $args): void
    {
        $r = $this->find((int) $args['id']);
        $new = $r['status'] === 'active' ? 'suspended' : 'active';
        Db::exec('UPDATE resellers SET status=:s WHERE id=:id', [':s' => $new, ':id' => $r['id']]);
        AuditLogger::log('reseller.status', 'reseller', (int) $r['id'], ['status' => $new]);
        flash('success', $new === 'active' ? 'نماینده فعال شد.' : 'نماینده معلق شد.');
        redirect_back('/owner/resellers');
    }

    public function destroy(Request $request, array $args): void
    {
        $r = $this->find((int) $args['id']);
        $active = (int) Db::scalar('SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status IN ("active","disabled")', [':id' => $r['id']]);
        if ($active > 0) {
            flash('error', 'ابتدا کانفیگ‌های فعال این نماینده را حذف کنید (' . $active . ' کانفیگ).');
            redirect_back('/owner/resellers/' . $r['id']);
        }
        Db::exec('DELETE FROM resellers WHERE id=:id', [':id' => $r['id']]);
        AuditLogger::log('reseller.delete', 'reseller', (int) $r['id'], ['username' => $r['username']]);
        flash('success', 'نماینده حذف شد.');
        Response::redirect('/owner/resellers');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function find(int $id): array
    {
        $r = Db::one('SELECT * FROM resellers WHERE id=:id', [':id' => $id]);
        if (!$r) {
            Response::abort(404, 'نماینده یافت نشد');
        }
        return $r;
    }

    /** Build the shared field => bind map from the request. */
    private function fields(Request $request): array
    {
        $perms = [];
        foreach (self::PERMS as $p) {
            $perms[$p] = $request->bool('perm_' . $p);
        }
        $squads = array_values(array_filter((array) $request->arr('allowed_squads')));
        $nullableInt = fn(string $k) => ($v = $request->post($k)) === '' || $v === null ? null : (int) $v;

        $exp = trim((string) $request->post('access_expires_at'));
        return [
            ':dn'     => trim((string) $request->post('display_name')),
            ':tg'     => ($t = trim((string) $request->post('telegram_id'))) === '' ? null : $t,
            ':notes'  => trim((string) $request->post('notes')) ?: null,
            ':status' => $request->post('status') === 'suspended' ? 'suspended' : 'active',
            ':exp'    => $exp === '' ? null : $exp,
            ':bal'    => (int) $request->post('balance', 0),
            ':adebt'  => $request->bool('allow_debt') ? 1 : 0,
            ':dlimit' => $nullableInt('debt_limit'),
            ':mu'     => (int) $request->post('max_users', 0),
            ':mupd'   => (int) $request->post('max_users_per_day', 0),
            ':minv'   => (int) $request->post('min_volume_gb', 0),
            ':maxv'   => (int) $request->post('max_volume_gb', 0),
            ':mind'   => (int) $request->post('min_days', 0),
            ':maxd'   => (int) $request->post('max_days', 0),
            ':pool'   => (int) $request->post('max_total_traffic_gb', 0),
            ':hwid'   => (int) $request->post('hwid_device_limit', 0),
            ':squads' => json_encode($squads, JSON_UNESCAPED_UNICODE),
            ':perms'  => json_encode($perms),
            ':ppg'    => $nullableInt('price_per_gb'),
            ':ppd'    => $nullableInt('price_per_day'),
            ':disc'   => $nullableInt('discount_percent'),
        ];
    }

    /** Live squads from Remnawave (graceful on failure). */
    private function squads(): array
    {
        try {
            return (new RemnawaveClient())->listInternalSquads();
        } catch (RemnawaveException $e) {
            flash('warning', 'دریافت لیست Squadها از Remnawave ناموفق بود: ' . $e->getMessage());
            return [];
        }
    }
}
