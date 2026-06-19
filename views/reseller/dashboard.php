<?php
$canRenew = \App\Services\ConfigService::perm($r, 'can_renew');
$usersRemaining = $stats['max_users'] > 0 ? max(0, $stats['max_users'] - $stats['used_slots']) : null; // null = unlimited
$gbRemaining = $stats['pool_gb'] > 0 ? max(0, $stats['pool_gb'] - $stats['allocated_gb']) : null;
$usersPct = $stats['max_users'] > 0 ? min(100, round($stats['used_slots'] / $stats['max_users'] * 100)) : 0;
$gbPct = $stats['pool_gb'] > 0 ? min(100, round($stats['allocated_gb'] / $stats['pool_gb'] * 100)) : 0;
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
  <div class="<?= $stats['balance']<0?'bg-rose-600':'bg-emerald-600' ?> rounded-xl p-4"><div class="text-xs text-white/80">موجودی کیف پول</div><div class="text-lg font-bold mt-1"><?= toman($stats['balance']) ?></div></div>
  <div class="bg-brand rounded-xl p-4"><div class="text-xs text-white/80">کانفیگ فعال</div><div class="text-lg font-bold mt-1"><?= number_format($stats['active']) ?></div></div>
  <div class="bg-violet-600 rounded-xl p-4"><div class="text-xs text-white/80">ساخت امروز</div><div class="text-lg font-bold mt-1"><?= number_format($stats['today']) ?></div></div>
  <div class="bg-sky-600 rounded-xl p-4"><div class="text-xs text-white/80">کل فروش</div><div class="text-lg font-bold mt-1"><?= toman($stats['sales']) ?></div></div>
</div>

<!-- Remaining quotas -->
<div class="grid sm:grid-cols-2 gap-3 mb-6">
  <div class="glass rounded-2xl p-4">
    <div class="flex justify-between items-baseline mb-2">
      <span class="text-sm text-stone-300">یوزر/کانفیگ باقی‌مانده</span>
      <span class="font-bold text-lg"><?= $usersRemaining === null ? 'نامحدود' : '<span class="text-brand-light">'.number_format($usersRemaining).'</span> از '.number_format($stats['max_users']) ?></span>
    </div>
    <?php if ($usersRemaining !== null): ?>
      <div class="h-2 bg-white/10 rounded"><div class="h-2 rounded <?= $usersPct>=90?'bg-rose-500':'bg-brand' ?>" style="width:<?= $usersPct ?>%"></div></div>
      <div class="text-[11px] text-stone-500 mt-1">مصرف‌شده: <?= number_format($stats['used_slots']) ?> (<?= $usersPct ?>٪)</div>
    <?php else: ?><div class="text-[11px] text-stone-500">سقف تعداد کانفیگ برای شما تعیین نشده است.</div><?php endif; ?>
  </div>

  <div class="glass rounded-2xl p-4">
    <div class="flex justify-between items-baseline mb-2">
      <span class="text-sm text-stone-300">حجم باقی‌مانده</span>
      <span class="font-bold text-lg"><?= $gbRemaining === null ? 'نامحدود' : '<span class="text-brand-light">'.number_format($gbRemaining).'</span> از '.number_format($stats['pool_gb']).' گیگ' ?></span>
    </div>
    <?php if ($gbRemaining !== null): ?>
      <div class="h-2 bg-white/10 rounded"><div class="h-2 rounded <?= $gbPct>=90?'bg-rose-500':'bg-emerald-500' ?>" style="width:<?= $gbPct ?>%"></div></div>
      <div class="text-[11px] text-stone-500 mt-1">تخصیص‌یافته: <?= number_format($stats['allocated_gb']) ?> گیگ (<?= $gbPct ?>٪)</div>
    <?php else: ?><div class="text-[11px] text-stone-500">سقف کل ترافیک برای شما تعیین نشده است.</div><?php endif; ?>
  </div>
</div>

<?php if ($canCreate): ?>
<a href="/panel/configs/create" class="inline-block btn-brand px-5 py-2.5 rounded-lg font-semibold mb-6">➕ ساخت کانفیگ جدید</a>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">⏰ نزدیک به انقضا (۵ روز)</h3>
    <?php if (!$expiring): ?>
      <p class="text-sm text-stone-500">کانفیگی نزدیک انقضا نیست.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <tbody>
        <?php foreach ($expiring as $c): ?>
          <tr class="border-b border-line">
            <td class="py-2 font-mono text-xs"><a href="/panel/configs/<?= $c['id'] ?>" class="text-brand"><?= e($c['remnawave_username']) ?></a></td>
            <td class="py-2 text-xs text-amber-400"><?= shamsi($c['expires_at'],'date') ?></td>
            <td class="py-2 text-left">
              <?php if ($canRenew): ?>
              <form method="post" action="/panel/configs/<?= $c['id'] ?>/renew" onsubmit="return confirm('تمدید <?= (int)$c['duration_days'] ?> روزه این کانفیگ؟')">
                <?= csrf_field() ?>
                <input type="hidden" name="add_days" value="<?= (int)$c['duration_days'] ?>">
                <input type="hidden" name="add_gb" value="0">
                <button class="btn-brand px-3 py-1 rounded-lg text-xs whitespace-nowrap">⚡ تمدید سریع</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">آخرین کانفیگ‌ها</h3>
    <table class="w-full text-sm">
      <tbody>
      <?php foreach ($recent as $c): ?>
        <tr class="border-b border-line">
          <td class="py-2 font-mono text-xs"><a href="/panel/configs/<?= $c['id'] ?>" class="text-brand"><?= e($c['remnawave_username']) ?></a></td>
          <td class="py-2 text-xs"><?= (int)$c['volume_gb'] ?>گیگ</td>
          <td class="py-2 text-xs"><?= human_bytes((int)$c['last_used_bytes']) ?></td>
          <td class="py-2 text-xs"><?= e($c['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td class="py-3 text-stone-500 text-sm">هنوز کانفیگی نساخته‌اید.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
