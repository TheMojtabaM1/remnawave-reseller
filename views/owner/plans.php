<?php $strategies = ['NO_RESET'=>'بدون ریست','DAY'=>'روزانه','WEEK'=>'هفتگی','MONTH'=>'ماهانه']; ?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-1 bg-card border border-line rounded-xl p-4 h-fit">
    <h3 class="font-semibold mb-3">پلن جدید</h3>
    <form method="post" action="/owner/plans" class="space-y-3">
      <?= csrf_field() ?>
      <input name="name" placeholder="نام پلن" required class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
      <div class="grid grid-cols-2 gap-2">
        <input type="number" name="volume_gb" placeholder="حجم (گیگ)" required class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <input type="number" name="duration_days" placeholder="مدت (روز)" required class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
      </div>
      <input type="number" name="price" placeholder="قیمت (تومان)" required class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
      <div class="grid grid-cols-2 gap-2">
        <input type="number" name="hwid_limit" placeholder="HWID" value="0" class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <select name="traffic_strategy" class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
          <?php foreach ($strategies as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
        </select>
      </div>
      <details class="text-sm">
        <summary class="cursor-pointer text-stone-400">Squadهای پلن</summary>
        <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
          <?php foreach ($squads as $s): ?>
            <label class="pick"><input type="checkbox" name="allowed_squads[]" value="<?= e($s['uuid']) ?>"> <?= e($s['name']) ?></label>
          <?php endforeach; ?>
          <?php if (!$squads): ?><span class="text-rose-400 text-xs">Squadها دریافت نشد.</span><?php endif; ?>
        </div>
      </details>
      <button class="w-full btn-brand py-2 rounded-lg text-sm">ایجاد پلن</button>
    </form>
  </div>

  <div class="lg:col-span-2 space-y-3">
    <?php foreach ($plans as $p):
      $psquads = json_decode((string)($p['allowed_squads']??'[]'), true) ?: []; ?>
      <div class="bg-card border border-line rounded-xl p-4">
        <form method="post" action="/owner/plans/<?= $p['id'] ?>" class="grid md:grid-cols-6 gap-2 items-end">
          <?= csrf_field() ?>
          <div class="md:col-span-2"><label class="text-xs text-stone-400">نام</label><input name="name" value="<?= e($p['name']) ?>" class="w-full bg-card2 border border-line2 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-stone-400">حجم</label><input type="number" name="volume_gb" value="<?= (int)$p['volume_gb'] ?>" class="w-full bg-card2 border border-line2 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-stone-400">مدت</label><input type="number" name="duration_days" value="<?= (int)$p['duration_days'] ?>" class="w-full bg-card2 border border-line2 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-stone-400">قیمت</label><input type="number" name="price" value="<?= (int)$p['price'] ?>" class="w-full bg-card2 border border-line2 rounded-lg px-2 py-1.5 text-sm"></div>
          <div><label class="text-xs text-stone-400">وضعیت</label>
            <select name="status" class="w-full bg-card2 border border-line2 rounded-lg px-2 py-1.5 text-sm">
              <option value="active" <?= $p['status']==='active'?'selected':'' ?>>فعال</option>
              <option value="inactive" <?= $p['status']!=='active'?'selected':'' ?>>غیرفعال</option>
            </select>
          </div>
          <input type="hidden" name="traffic_strategy" value="<?= e($p['traffic_strategy']) ?>">
          <input type="hidden" name="hwid_limit" value="<?= (int)$p['hwid_limit'] ?>">
          <?php foreach ($psquads as $u): ?><input type="hidden" name="allowed_squads[]" value="<?= e($u) ?>"><?php endforeach; ?>
          <div class="md:col-span-6 flex gap-2 pt-2">
            <button class="bg-brand hover:bg-brand-light px-4 py-1.5 rounded-lg text-xs">ذخیره</button>
          </div>
        </form>
        <form method="post" action="/owner/plans/<?= $p['id'] ?>/delete" onsubmit="return confirm('حذف پلن؟')" class="mt-1">
          <?= csrf_field() ?><button class="text-rose-400 text-xs hover:underline">حذف پلن</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$plans): ?><div class="bg-card border border-line rounded-xl p-6 text-center text-stone-500">پلنی تعریف نشده است.</div><?php endif; ?>
  </div>
</div>
