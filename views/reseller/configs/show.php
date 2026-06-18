<?php
$usedGb = bytes_to_gb((int)$c['last_used_bytes']);
$usedPct = (int)$c['volume_gb'] > 0 ? min(100, round($usedGb / (int)$c['volume_gb'] * 100)) : 0;
$sub = (string) $c['subscription_url'];
?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold font-mono"><?= e($c['remnawave_username']) ?></h2>
  <a href="/panel/configs" class="bg-white/10 px-4 py-2 rounded-lg text-sm">بازگشت</a>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <!-- Subscription + QR -->
  <div class="bg-card border border-line rounded-xl p-4 lg:col-span-2">
    <h3 class="font-semibold mb-3">لینک اشتراک</h3>
    <?php if ($sub): ?>
      <div class="flex gap-2 items-center mb-3">
        <input id="suburl" value="<?= e($sub) ?>" readonly class="flex-1 bg-card2 border border-line2 rounded-lg px-3 py-2 text-xs font-mono">
        <button type="button" onclick="copyText(document.getElementById('suburl').value, this)" class="btn-brand px-4 py-2 rounded-lg text-sm whitespace-nowrap">کپی</button>
      </div>
      <div class="flex justify-center bg-white rounded-lg p-3 w-fit"><img src="/panel/configs/<?= $c['id'] ?>/qr" alt="QR" width="180" height="180"></div>
    <?php else: ?>
      <p class="text-sm text-rose-400">لینک اشتراک در دسترس نیست.</p>
    <?php endif; ?>
  </div>

  <!-- Details -->
  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">مشخصات</h3>
    <div class="text-sm space-y-2">
      <div class="flex justify-between"><span class="text-stone-400">حجم</span><span><?= (int)$c['volume_gb'] ?> گیگ</span></div>
      <div>
        <div class="flex justify-between text-xs mb-1"><span class="text-stone-400">مصرف</span><span><?= human_bytes((int)$c['last_used_bytes']) ?> (<?= $usedPct ?>٪)</span></div>
        <div class="h-2 bg-white/10 rounded"><div class="h-2 bg-emerald-500 rounded" style="width:<?= $usedPct ?>%"></div></div>
      </div>
      <div class="flex justify-between"><span class="text-stone-400">انقضا</span><span><?= jdate($c['expires_at'],'date') ?></span></div>
      <div class="flex justify-between"><span class="text-stone-400">قیمت پرداختی</span><span><?= toman((int)$c['price_charged']) ?></span></div>
      <div class="flex justify-between"><span class="text-stone-400">وضعیت</span><span><?= e($c['status']) ?></span></div>
      <div class="flex justify-between"><span class="text-stone-400">تاریخ ساخت</span><span class="text-xs"><?= jdate($c['created_at']) ?></span></div>
    </div>
  </div>
</div>

<!-- Actions -->
<div class="grid lg:grid-cols-2 gap-4 mt-4">
  <?php if ($perm('can_renew')): ?>
  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">تمدید / افزایش حجم</h3>
    <form method="post" action="/panel/configs/<?= $c['id'] ?>/renew" class="flex flex-wrap gap-2 items-end">
      <?= csrf_field() ?>
      <div><label class="block text-xs text-stone-400 mb-1">افزودن روز</label><input type="number" name="add_days" value="0" min="0" class="w-28 bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
      <div><label class="block text-xs text-stone-400 mb-1">افزودن حجم (گیگ)</label><input type="number" name="add_gb" value="0" min="0" class="w-28 bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
      <button class="btn-brand px-4 py-2 rounded-lg text-sm">تمدید</button>
    </form>
    <p class="text-[11px] text-stone-500 mt-2">هزینه بر اساس نرخ هر گیگ/روز شما محاسبه و از کیف پول کسر می‌شود.</p>
  </div>
  <?php endif; ?>

  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">عملیات</h3>
    <div class="flex flex-wrap gap-2">
      <?php if ($perm('can_edit_config')): ?>
        <form method="post" action="/panel/configs/<?= $c['id'] ?>/toggle"><?= csrf_field() ?>
          <button class="<?= $c['status']==='active'?'bg-amber-600 hover:bg-amber-500':'btn-brand' ?> px-4 py-2 rounded-lg text-sm"><?= $c['status']==='active'?'غیرفعال‌سازی':'فعال‌سازی' ?></button>
        </form>
      <?php endif; ?>
      <?php if ($perm('can_regenerate_subscription')): ?>
        <form method="post" action="/panel/configs/<?= $c['id'] ?>/regenerate" onsubmit="return confirm('لینک قبلی باطل می‌شود. ادامه؟')"><?= csrf_field() ?>
          <button class="bg-brand hover:bg-brand-light px-4 py-2 rounded-lg text-sm">بازتولید لینک</button>
        </form>
      <?php endif; ?>
      <?php if ($perm('can_delete_config')): ?>
        <form method="post" action="/panel/configs/<?= $c['id'] ?>/delete" onsubmit="return confirm('حذف کانفیگ؟ بازگشت وجه بر اساس حجم استفاده‌نشده محاسبه می‌شود.')"><?= csrf_field() ?>
          <button class="bg-rose-600 hover:bg-rose-500 px-4 py-2 rounded-lg text-sm">حذف</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
