<?php
$perms = json_decode((string) ($r['permissions'] ?? '[]'), true) ?: [];
$allowedSquads = json_decode((string) ($r['allowed_squads'] ?? '[]'), true) ?: [];
$squadNames = [];
foreach ($squads as $s) { $squadNames[$s['uuid']] = $s['name']; }
$typeLabels = ['topup' => 'شارژ', 'charge' => 'فروش', 'refund' => 'بازگشت وجه', 'manual_adjust' => 'اصلاح دستی', 'gift' => 'هدیه'];
?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold"><?= e($r['display_name'] ?: $r['username']) ?> <span class="text-sm text-stone-500">(<?= e($r['username']) ?>)</span></h2>
  <div class="flex gap-2">
    <form method="post" action="/owner/resellers/<?= $r['id'] ?>/sync"><?= csrf_field() ?><button class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm">🔄 همگام‌سازی مصرف</button></form>
    <a href="/owner/resellers/<?= $r['id'] ?>/edit" class="bg-amber-600 hover:bg-amber-500 px-4 py-2 rounded-lg text-sm">ویرایش</a>
    <a href="/owner/resellers" class="bg-white/10 px-4 py-2 rounded-lg text-sm">بازگشت</a>
  </div>
</div>

<div class="grid md:grid-cols-4 gap-3 mb-4">
  <div class="bg-card border border-line rounded-xl p-4"><div class="text-xs text-stone-400">موجودی</div><div class="text-xl font-bold <?= (int)$r['balance']<0?'text-rose-400':'text-emerald-400' ?>"><?= toman((int)$r['balance']) ?></div></div>
  <div class="bg-card border border-line rounded-xl p-4"><div class="text-xs text-stone-400">کانفیگ فعال</div><div class="text-xl font-bold"><?= (int)$stats['active'] ?></div></div>
  <div class="bg-card border border-line rounded-xl p-4"><div class="text-xs text-stone-400">کل فروش</div><div class="text-xl font-bold"><?= toman((int)$stats['sales']) ?></div></div>
  <div class="bg-card border border-line rounded-xl p-4"><div class="text-xs text-stone-400">وضعیت</div><div class="text-xl font-bold <?= $r['status']==='active'?'text-emerald-400':'text-rose-400' ?>"><?= $r['status']==='active'?'فعال':'معلق' ?></div></div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <!-- Wallet management -->
  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">مدیریت کیف پول</h3>
    <form method="post" action="/owner/resellers/<?= $r['id'] ?>/wallet" class="space-y-3">
      <?= csrf_field() ?>
      <select name="operation" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <option value="add">افزایش موجودی (شارژ)</option>
        <option value="subtract">کاهش موجودی</option>
        <option value="gift">هدیه</option>
        <option value="set">تنظیم به مقدار مشخص</option>
        <option value="zero">صفر کردن موجودی</option>
      </select>
      <input type="number" name="amount" placeholder="مبلغ (تومان)" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
      <input name="reason" placeholder="دلیل / توضیح" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
      <button class="w-full bg-brand hover:bg-brand-light py-2 rounded-lg text-sm">اعمال</button>
    </form>
  </div>

  <!-- Limits & permissions summary -->
  <div class="bg-card border border-line rounded-xl p-4 lg:col-span-2">
    <h3 class="font-semibold mb-3">محدودیت‌ها و دسترسی‌ها</h3>
    <div class="grid md:grid-cols-2 gap-x-6 gap-y-1 text-sm">
      <div class="flex justify-between border-b border-line py-1"><span class="text-stone-400">حداکثر کانفیگ</span><span><?= (int)$r['max_users'] ?: 'نامحدود' ?></span></div>
      <div class="flex justify-between border-b border-line py-1"><span class="text-stone-400">کانفیگ روزانه</span><span><?= (int)$r['max_users_per_day'] ?: 'نامحدود' ?></span></div>
      <div class="flex justify-between border-b border-line py-1"><span class="text-stone-400">بازه حجم</span><span><?= (int)$r['min_volume_gb'] ?>–<?= (int)$r['max_volume_gb'] ?: '∞' ?> گیگ</span></div>
      <div class="flex justify-between border-b border-line py-1"><span class="text-stone-400">بازه مدت</span><span><?= (int)$r['min_days'] ?>–<?= (int)$r['max_days'] ?: '∞' ?> روز</span></div>
      <div class="flex justify-between border-b border-line py-1"><span class="text-stone-400">سقف کل ترافیک</span><span><?= (int)$r['max_total_traffic_gb'] ?: 'نامحدود' ?> گیگ</span></div>
      <div class="flex justify-between border-b border-line py-1"><span class="text-stone-400">سقف بدهی</span><span><?= $r['allow_debt'] ? ($r['debt_limit']===null?'نامحدود':toman((int)$r['debt_limit'])) : 'بدون بدهی' ?></span></div>
    </div>
    <div class="mt-3 flex flex-wrap gap-1">
      <?php foreach ($perms as $k => $on): if ($on): ?>
        <span class="bg-emerald-600/20 text-emerald-300 text-xs px-2 py-1 rounded"><?= e($k) ?></span>
      <?php endif; endforeach; ?>
    </div>
    <div class="mt-3 text-xs text-stone-400">Squadهای مجاز:
      <?php if (!$allowedSquads): ?>همه<?php else: foreach ($allowedSquads as $u): ?>
        <span class="bg-card2 px-2 py-1 rounded mr-1"><?= e($squadNames[$u] ?? $u) ?></span>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Configs -->
<div class="bg-card border border-line rounded-xl p-4 mt-4">
  <h3 class="font-semibold mb-3">کانفیگ‌ها (<?= count($configs) ?>)</h3>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right p-2">یوزرنیم</th><th class="text-right p-2">حجم</th><th class="text-right p-2">مصرف</th><th class="text-right p-2">انقضا</th><th class="text-right p-2">قیمت</th><th class="text-right p-2">وضعیت</th></tr></thead>
      <tbody>
      <?php foreach ($configs as $c): ?>
        <tr class="border-t border-line">
          <td class="p-2 font-mono text-xs"><?= e($c['remnawave_username']) ?></td>
          <td class="p-2"><?= (int)$c['volume_gb'] ?> گیگ</td>
          <td class="p-2"><?= human_bytes((int)$c['last_used_bytes']) ?></td>
          <td class="p-2 text-xs"><?= shamsi($c['expires_at'],'date') ?></td>
          <td class="p-2"><?= toman((int)$c['price_charged']) ?></td>
          <td class="p-2"><span class="text-xs"><?= e($c['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$configs): ?><tr><td colspan="6" class="p-4 text-center text-stone-500">کانفیگی نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Archived (deleted) configs — restore (#170) -->
<?php if (!empty($archived)): ?>
<div class="bg-card border border-line rounded-xl p-4 mt-4">
  <h3 class="font-semibold mb-3">🗄️ بایگانی کانفیگ‌های حذف‌شده (<?= count($archived) ?>)</h3>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right p-2">یوزرنیم</th><th class="text-right p-2">حجم</th><th class="text-right p-2">مدت</th><th class="text-right p-2">بازیابی</th></tr></thead>
      <tbody>
      <?php foreach ($archived as $c): ?>
        <tr class="border-t border-line">
          <td class="p-2 font-mono text-xs"><?= e($c['remnawave_username']) ?></td>
          <td class="p-2"><?= (int)$c['volume_gb'] ?>گیگ</td>
          <td class="p-2"><?= (int)$c['duration_days'] ?>روز</td>
          <td class="p-2">
            <form method="post" action="/owner/configs/<?= $c['id'] ?>/restore" onsubmit="return confirm('بازیابی این کانفیگ روی سرور؟ (بدون کسر هزینه)')">
              <?= csrf_field() ?><button class="bg-emerald-600 hover:bg-emerald-500 px-3 py-1 rounded-lg text-xs">بازیابی</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Transactions -->
<div class="bg-card border border-line rounded-xl p-4 mt-4">
  <h3 class="font-semibold mb-3">تراکنش‌های اخیر</h3>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right p-2">تاریخ</th><th class="text-right p-2">نوع</th><th class="text-right p-2">مبلغ</th><th class="text-right p-2">مانده</th><th class="text-right p-2">شرح</th></tr></thead>
      <tbody>
      <?php foreach ($txs as $t): ?>
        <tr class="border-t border-line">
          <td class="p-2 text-xs"><?= shamsi($t['created_at']) ?></td>
          <td class="p-2"><?= e($typeLabels[$t['type']] ?? $t['type']) ?></td>
          <td class="p-2 <?= (int)$t['amount']<0?'text-rose-400':'text-emerald-400' ?>"><?= toman((int)$t['amount']) ?></td>
          <td class="p-2"><?= toman((int)$t['balance_after']) ?></td>
          <td class="p-2 text-xs text-stone-400"><?= e($t['description']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$txs): ?><tr><td colspan="5" class="p-4 text-center text-stone-500">تراکنشی نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
