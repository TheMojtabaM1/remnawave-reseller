<?php $canRenew = \App\Services\ConfigService::perm($r, 'can_renew'); ?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <div class="<?= $stats['balance']<0?'bg-rose-600':'bg-emerald-600' ?> rounded-xl p-4"><div class="text-xs text-white/80">موجودی کیف پول</div><div class="text-lg font-bold mt-1"><?= toman($stats['balance']) ?></div></div>
  <div class="bg-brand rounded-xl p-4"><div class="text-xs text-white/80">کانفیگ فعال</div><div class="text-lg font-bold mt-1"><?= number_format($stats['active']) ?></div></div>
  <div class="bg-violet-600 rounded-xl p-4"><div class="text-xs text-white/80">حجم تخصیص‌یافته</div><div class="text-lg font-bold mt-1"><?= number_format($stats['allocated_gb']) ?> گیگ</div></div>
  <div class="bg-amber-600 rounded-xl p-4"><div class="text-xs text-white/80">ساخت امروز</div><div class="text-lg font-bold mt-1"><?= number_format($stats['today']) ?></div></div>
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
