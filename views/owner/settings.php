<?php $strategies = ['NO_RESET'=>'بدون ریست','DAY'=>'روزانه','WEEK'=>'هفتگی','MONTH'=>'ماهانه']; ?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">تنظیمات عمومی</h3>
    <form method="post" action="/owner/settings" class="space-y-3">
      <?= csrf_field() ?>
      <div><label class="block text-xs text-stone-400 mb-1">نام برنامه</label>
        <input name="app_name" value="<?= e($settings['app_name']) ?>" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
      <div class="grid md:grid-cols-2 gap-3">
        <div><label class="block text-xs text-stone-400 mb-1">روزهای مهلت پاک‌سازی منقضی‌ها</label>
          <input type="number" name="cleanup_grace_days" value="<?= e($settings['cleanup_grace_days']) ?>" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-stone-400 mb-1">آستانه هشدار موجودی کم (تومان)</label>
          <input type="number" name="low_balance_threshold" value="<?= e($settings['low_balance_threshold']) ?>" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-stone-400 mb-1">استراتژی ترافیک پیش‌فرض</label>
          <select name="default_traffic_strategy" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
            <?php foreach ($strategies as $k=>$v): ?><option value="<?= $k ?>" <?= $settings['default_traffic_strategy']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
          </select></div>
        <div><label class="block text-xs text-stone-400 mb-1">آستانه جهش ترافیک (گیگ/روز)</label>
          <input type="number" name="traffic_spike_gb" value="<?= e($settings['traffic_spike_gb']) ?>" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
      </div>

      <div class="border-t border-line pt-3 mt-2">
        <div class="text-sm font-semibold text-brand-light mb-2">قیمت‌گذاری پیش‌فرض (برای همه نمایندگان)</div>
        <p class="text-[11px] text-stone-500 mb-3">اگر برای نماینده‌ای قیمت اختصاصی یا پله‌ی قیمتی تعریف نشده باشد، این نرخ‌ها مبنای محاسبه‌ی کانفیگ سفارشی و تمدید هستند.</p>
        <div class="grid md:grid-cols-2 gap-3">
          <div><label class="block text-xs text-stone-400 mb-1">قیمت پیش‌فرض هر گیگ (تومان)</label>
            <input type="number" name="default_price_per_gb" value="<?= e($settings['default_price_per_gb']) ?>" placeholder="مثلاً ۱۵۰۰" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
          <div><label class="block text-xs text-stone-400 mb-1">قیمت پیش‌فرض هر روز (تومان)</label>
            <input type="number" name="default_price_per_day" value="<?= e($settings['default_price_per_day']) ?>" placeholder="مثلاً ۰" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
        </div>
      </div>

      <div class="border-t border-line pt-3 mt-2">
        <div class="text-sm font-semibold text-brand-light mb-2">پیام سراسری به نمایندگان (Broadcast)</div>
        <label class="pick w-fit mb-2">
          <input type="hidden" name="broadcast_enabled" value="0">
          <input type="checkbox" name="broadcast_enabled" value="1" <?= ($settings['broadcast_enabled'] ?? '')==='1'?'checked':'' ?>> نمایش بنر اعلان به همه نمایندگان
        </label>
        <textarea name="broadcast_message" rows="2" placeholder="مثلاً: امشب از ساعت ۲ تا ۴ بامداد سرور بروزرسانی می‌شود." class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"><?= e($settings['broadcast_message'] ?? '') ?></textarea>
      </div>

      <button class="btn-brand px-6 py-2 rounded-lg text-sm">ذخیره</button>
    </form>
  </div>

  <div class="bg-card border border-line rounded-xl p-4 h-fit">
    <h3 class="font-semibold mb-3">اتصال Remnawave</h3>
    <div class="text-sm space-y-2">
      <div class="text-stone-400 text-xs">آدرس پنل:</div>
      <div class="font-mono text-xs break-all"><?= e($rwBase) ?></div>
      <div class="mt-3">
        <?php if ($rwStatus === 'ok'): ?>
          <span class="bg-emerald-600/20 text-emerald-400 px-3 py-1.5 rounded-lg text-sm">🟢 اتصال برقرار است</span>
        <?php else: ?>
          <span class="bg-rose-600/20 text-rose-400 px-3 py-1.5 rounded-lg text-sm">🔴 اتصال ناموفق</span>
          <div class="text-xs text-rose-300 mt-2"><?= e($rwError) ?></div>
        <?php endif; ?>
      </div>
      <p class="text-[11px] text-stone-500 mt-3">آدرس و توکن API در فایل <code>.env</code> تنظیم می‌شوند.</p>
    </div>
  </div>
</div>
