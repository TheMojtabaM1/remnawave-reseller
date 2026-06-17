<?php $strategies = ['NO_RESET'=>'بدون ریست','DAY'=>'روزانه','WEEK'=>'هفتگی','MONTH'=>'ماهانه']; ?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 h-fit">
    <h3 class="font-semibold mb-3">قالب جدید</h3>
    <form method="post" action="/owner/templates" class="space-y-3">
      <?= csrf_field() ?>
      <input name="name" placeholder="نام قالب" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      <div class="grid grid-cols-2 gap-2">
        <input type="number" name="volume_gb" placeholder="حجم (گیگ)" required class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
        <input type="number" name="duration_days" placeholder="مدت (روز)" required class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      </div>
      <div class="grid grid-cols-2 gap-2">
        <input type="number" name="hwid_limit" placeholder="HWID" value="0" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
        <select name="traffic_strategy" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
          <?php foreach ($strategies as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
        </select>
      </div>
      <input name="naming_pattern" placeholder="الگوی نام‌گذاری (اختیاری)" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      <details class="text-sm"><summary class="cursor-pointer text-slate-400">Squadها</summary>
        <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
          <?php foreach ($squads as $s): ?><label class="flex items-center gap-2"><input type="checkbox" name="squads[]" value="<?= e($s['uuid']) ?>"> <?= e($s['name']) ?></label><?php endforeach; ?>
        </div>
      </details>
      <button class="w-full bg-emerald-600 hover:bg-emerald-500 py-2 rounded-lg text-sm">ایجاد قالب</button>
    </form>
  </div>

  <div class="lg:col-span-2 space-y-3">
    <?php foreach ($templates as $t):
      $ts = json_decode((string)($t['squads']??'[]'), true) ?: []; ?>
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <form method="post" action="/owner/templates/<?= $t['id'] ?>" class="grid md:grid-cols-5 gap-2 items-end">
          <?= csrf_field() ?>
          <div class="md:col-span-2"><label class="text-xs text-slate-400">نام</label><input name="name" value="<?= e($t['name']) ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-slate-400">حجم</label><input type="number" name="volume_gb" value="<?= (int)$t['volume_gb'] ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-slate-400">مدت</label><input type="number" name="duration_days" value="<?= (int)$t['duration_days'] ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-slate-400">HWID</label><input type="number" name="hwid_limit" value="<?= (int)$t['hwid_limit'] ?>" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2 py-1.5 text-sm"></div>
          <input type="hidden" name="traffic_strategy" value="<?= e($t['traffic_strategy']) ?>">
          <input type="hidden" name="naming_pattern" value="<?= e($t['naming_pattern']) ?>">
          <?php foreach ($ts as $u): ?><input type="hidden" name="squads[]" value="<?= e($u) ?>"><?php endforeach; ?>
          <div class="md:col-span-5 flex gap-2 pt-1"><button class="bg-sky-600 hover:bg-sky-500 px-4 py-1.5 rounded-lg text-xs">ذخیره</button></div>
        </form>
        <form method="post" action="/owner/templates/<?= $t['id'] ?>/delete" onsubmit="return confirm('حذف قالب؟')"><?= csrf_field() ?><button class="text-rose-400 text-xs hover:underline">حذف</button></form>
      </div>
    <?php endforeach; ?>
    <?php if (!$templates): ?><div class="bg-slate-900 border border-slate-800 rounded-xl p-6 text-center text-slate-500">قالبی تعریف نشده است.</div><?php endif; ?>
  </div>
</div>
