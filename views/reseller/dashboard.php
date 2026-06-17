<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <div class="<?= $stats['balance']<0?'bg-rose-600':'bg-emerald-600' ?> rounded-xl p-4"><div class="text-xs text-white/80">موجودی کیف پول</div><div class="text-lg font-bold mt-1"><?= toman($stats['balance']) ?></div></div>
  <div class="bg-sky-600 rounded-xl p-4"><div class="text-xs text-white/80">کانفیگ فعال</div><div class="text-lg font-bold mt-1"><?= number_format($stats['active']) ?></div></div>
  <div class="bg-violet-600 rounded-xl p-4"><div class="text-xs text-white/80">حجم تخصیص‌یافته</div><div class="text-lg font-bold mt-1"><?= number_format($stats['allocated_gb']) ?> گیگ</div></div>
  <div class="bg-amber-600 rounded-xl p-4"><div class="text-xs text-white/80">ساخت امروز</div><div class="text-lg font-bold mt-1"><?= number_format($stats['today']) ?></div></div>
</div>

<?php if ($canCreate): ?>
<a href="/panel/configs/create" class="inline-block bg-emerald-600 hover:bg-emerald-500 px-5 py-2.5 rounded-lg font-semibold mb-6">➕ ساخت کانفیگ جدید</a>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">⏰ نزدیک به انقضا (۵ روز)</h3>
    <?php if (!$expiring): ?>
      <p class="text-sm text-slate-500">کانفیگی نزدیک انقضا نیست.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <tbody>
        <?php foreach ($expiring as $c): ?>
          <tr class="border-b border-slate-800">
            <td class="py-2 font-mono text-xs"><a href="/panel/configs/<?= $c['id'] ?>" class="text-sky-400"><?= e($c['remnawave_username']) ?></a></td>
            <td class="py-2 text-xs text-amber-400"><?= jdate($c['expires_at'],'date') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">آخرین کانفیگ‌ها</h3>
    <table class="w-full text-sm">
      <tbody>
      <?php foreach ($recent as $c): ?>
        <tr class="border-b border-slate-800">
          <td class="py-2 font-mono text-xs"><a href="/panel/configs/<?= $c['id'] ?>" class="text-sky-400"><?= e($c['remnawave_username']) ?></a></td>
          <td class="py-2 text-xs"><?= (int)$c['volume_gb'] ?>گیگ</td>
          <td class="py-2 text-xs"><?= human_bytes((int)$c['last_used_bytes']) ?></td>
          <td class="py-2 text-xs"><?= e($c['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td class="py-3 text-slate-500 text-sm">هنوز کانفیگی نساخته‌اید.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
