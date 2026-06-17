<?php
$isEdit = $reseller !== null;
$action = $isEdit ? '/owner/resellers/' . $reseller['id'] : '/owner/resellers';
$permVals = [];
$squadVals = [];
if ($isEdit) {
    $permVals = json_decode((string) ($reseller['permissions'] ?? '[]'), true) ?: [];
    $squadVals = json_decode((string) ($reseller['allowed_squads'] ?? '[]'), true) ?: [];
}
$v = function (string $k, $def = '') use ($isEdit, $reseller) {
    if ($isEdit && array_key_exists($k, $reseller) && $reseller[$k] !== null) {
        return e($reseller[$k]);
    }
    return old($k, $def);
};
$permLabels = [
    'can_create_config' => 'ساخت کانفیگ',
    'can_edit_config' => 'ویرایش کانفیگ',
    'can_delete_config' => 'حذف کانفیگ',
    'can_renew' => 'تمدید',
    'can_regenerate_subscription' => 'بازتولید لینک اشتراک',
    'can_create_custom' => 'ساخت کانفیگ سفارشی',
    'can_use_trial' => 'استفاده از تست (روزمپ)',
];
function field($label, $name, $value, $type = 'number', $hint = '') {
    echo '<div><label class="block text-xs text-slate-400 mb-1">' . e($label) . '</label>';
    echo '<input type="' . $type . '" name="' . e($name) . '" value="' . $value . '" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">';
    if ($hint) echo '<div class="text-[11px] text-slate-500 mt-1">' . e($hint) . '</div>';
    echo '</div>';
}
?>
<form method="post" action="<?= $action ?>" class="space-y-6 max-w-4xl">
  <?= csrf_field() ?>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">اطلاعات حساب</h3>
    <div class="grid md:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs text-slate-400 mb-1">نام کاربری *</label>
        <input name="username" value="<?= $v('username') ?>" <?= $isEdit ? 'disabled' : 'required' ?> class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm disabled:opacity-60">
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">رمز عبور <?= $isEdit ? '(خالی = بدون تغییر)' : '*' ?></label>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">پیشوند یوزرنیم *</label>
        <input name="prefix" value="<?= $v('prefix') ?>" <?= $isEdit ? 'disabled' : 'required' ?> placeholder="مثال: ali" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm disabled:opacity-60">
      </div>
      <?php field('نام نمایشی', 'display_name', $v('display_name'), 'text'); ?>
      <?php field('شناسه تلگرام', 'telegram_id', $v('telegram_id'), 'text'); ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1">انقضای دسترسی (UTC، خالی=نامحدود)</label>
        <input type="datetime-local" name="access_expires_at" value="<?= $isEdit && $reseller['access_expires_at'] ? e(str_replace(' ', 'T', substr($reseller['access_expires_at'],0,16))) : '' ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">وضعیت</label>
        <select name="status" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
          <option value="active" <?= $v('status','active')==='active'?'selected':'' ?>>فعال</option>
          <option value="suspended" <?= $v('status')==='suspended'?'selected':'' ?>>معلق</option>
        </select>
      </div>
      <div class="md:col-span-3">
        <label class="block text-xs text-slate-400 mb-1">یادداشت</label>
        <textarea name="notes" rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm"><?= $v('notes') ?></textarea>
      </div>
    </div>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">محدودیت‌ها <span class="text-xs text-slate-500">(۰ = نامحدود)</span></h3>
    <div class="grid md:grid-cols-4 gap-3">
      <?php field('حداکثر تعداد کانفیگ', 'max_users', $v('max_users','0')); ?>
      <?php field('حداکثر کانفیگ روزانه', 'max_users_per_day', $v('max_users_per_day','0')); ?>
      <?php field('حداقل حجم (گیگ)', 'min_volume_gb', $v('min_volume_gb','0')); ?>
      <?php field('حداکثر حجم (گیگ)', 'max_volume_gb', $v('max_volume_gb','0')); ?>
      <?php field('حداقل مدت (روز)', 'min_days', $v('min_days','0')); ?>
      <?php field('حداکثر مدت (روز)', 'max_days', $v('max_days','0')); ?>
      <?php field('سقف کل ترافیک (گیگ)', 'max_total_traffic_gb', $v('max_total_traffic_gb','0')); ?>
      <?php field('محدودیت دستگاه (HWID)', 'hwid_device_limit', $v('hwid_device_limit','0')); ?>
    </div>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">کیف پول و قیمت‌گذاری</h3>
    <div class="grid md:grid-cols-4 gap-3 items-end">
      <?php if (!$isEdit): ?>
        <?php field('موجودی اولیه (تومان)', 'balance', $v('balance','0')); ?>
      <?php endif; ?>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="allow_debt" value="1" <?= ($isEdit ? $reseller['allow_debt'] : false) ? 'checked' : '' ?> class="rounded"> اجازه بدهی
      </label>
      <?php field('سقف بدهی (خالی=نامحدود)', 'debt_limit', $isEdit ? $v('debt_limit') : old('debt_limit')); ?>
      <?php field('قیمت هر گیگ (override)', 'price_per_gb', $isEdit ? $v('price_per_gb') : old('price_per_gb')); ?>
      <?php field('قیمت هر روز (override)', 'price_per_day', $isEdit ? $v('price_per_day') : old('price_per_day')); ?>
      <?php field('درصد تخفیف', 'discount_percent', $isEdit ? $v('discount_percent') : old('discount_percent')); ?>
    </div>
    <p class="text-[11px] text-slate-500 mt-2">override‌های قیمت در صورت تعیین، بر قیمت پلن/سراسری ارجح‌اند. تغییر موجودی پس از ساخت، از صفحه نماینده انجام می‌شود.</p>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">دسترسی‌ها</h3>
    <div class="grid md:grid-cols-3 gap-2">
      <?php foreach ($perms as $p): ?>
        <label class="flex items-center gap-2 text-sm bg-slate-800/50 px-3 py-2 rounded-lg">
          <input type="checkbox" name="perm_<?= $p ?>" value="1" <?= !empty($permVals[$p]) ? 'checked' : '' ?> class="rounded">
          <?= e($permLabels[$p] ?? $p) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">Squadهای مجاز <span class="text-xs text-slate-500">(خالی = همه مجاز)</span></h3>
    <?php if (!$squads): ?>
      <p class="text-sm text-rose-400">لیست Squadها از Remnawave دریافت نشد.</p>
    <?php else: ?>
      <div class="grid md:grid-cols-3 gap-2">
        <?php foreach ($squads as $s): ?>
          <label class="flex items-center gap-2 text-sm bg-slate-800/50 px-3 py-2 rounded-lg">
            <input type="checkbox" name="allowed_squads[]" value="<?= e($s['uuid']) ?>" <?= in_array($s['uuid'], $squadVals, true) ? 'checked' : '' ?> class="rounded">
            <?= e($s['name']) ?> <span class="text-xs text-slate-500">(<?= (int)$s['members'] ?>)</span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="flex gap-3">
    <button class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2 rounded-lg font-semibold"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد نماینده' ?></button>
    <a href="/owner/resellers" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-lg">انصراف</a>
  </div>
</form>
