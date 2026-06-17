<?php $strategies = ['NO_RESET'=>'بدون ریست','DAY'=>'روزانه','WEEK'=>'هفتگی','MONTH'=>'ماهانه']; ?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">تنظیمات عمومی</h3>
    <form method="post" action="/owner/settings" class="space-y-3">
      <?= csrf_field() ?>
      <div><label class="block text-xs text-slate-400 mb-1">نام برنامه</label>
        <input name="app_name" value="<?= e($settings['app_name']) ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm"></div>
      <div class="grid md:grid-cols-2 gap-3">
        <div><label class="block text-xs text-slate-400 mb-1">روزهای مهلت پاک‌سازی منقضی‌ها</label>
          <input type="number" name="cleanup_grace_days" value="<?= e($settings['cleanup_grace_days']) ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">آستانه هشدار موجودی کم (تومان)</label>
          <input type="number" name="low_balance_threshold" value="<?= e($settings['low_balance_threshold']) ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">استراتژی ترافیک پیش‌فرض</label>
          <select name="default_traffic_strategy" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
            <?php foreach ($strategies as $k=>$v): ?><option value="<?= $k ?>" <?= $settings['default_traffic_strategy']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
          </select></div>
        <div><label class="block text-xs text-slate-400 mb-1">آستانه جهش ترافیک (گیگ/روز)</label>
          <input type="number" name="traffic_spike_gb" value="<?= e($settings['traffic_spike_gb']) ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm"></div>
      </div>
      <button class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2 rounded-lg text-sm">ذخیره</button>
    </form>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 h-fit">
    <h3 class="font-semibold mb-3">اتصال Remnawave</h3>
    <div class="text-sm space-y-2">
      <div class="text-slate-400 text-xs">آدرس پنل:</div>
      <div class="font-mono text-xs break-all"><?= e($rwBase) ?></div>
      <div class="mt-3">
        <?php if ($rwStatus === 'ok'): ?>
          <span class="bg-emerald-600/20 text-emerald-400 px-3 py-1.5 rounded-lg text-sm">🟢 اتصال برقرار است</span>
        <?php else: ?>
          <span class="bg-rose-600/20 text-rose-400 px-3 py-1.5 rounded-lg text-sm">🔴 اتصال ناموفق</span>
          <div class="text-xs text-rose-300 mt-2"><?= e($rwError) ?></div>
        <?php endif; ?>
      </div>
      <p class="text-[11px] text-slate-500 mt-3">آدرس و توکن API در فایل <code>.env</code> تنظیم می‌شوند.</p>
    </div>
  </div>
</div>
