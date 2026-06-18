<?php
$limits = [];
if ((int)$r['max_volume_gb'] > 0) $limits[] = 'حجم: '.$r['min_volume_gb'].'–'.$r['max_volume_gb'].' گیگ';
if ((int)$r['max_days'] > 0) $limits[] = 'مدت: '.$r['min_days'].'–'.$r['max_days'].' روز';
?>
<div class="max-w-2xl">
  <h2 class="text-lg font-semibold mb-4">ساخت کانفیگ جدید</h2>

  <?php if ($limits): ?>
    <div class="bg-card2/50 border border-line2 rounded-lg px-4 py-2 mb-4 text-xs text-stone-400">محدودیت‌های شما — <?= implode(' | ', array_map('e',$limits)) ?></div>
  <?php endif; ?>

  <div class="flex gap-2 mb-4">
    <button type="button" data-tab="plan" class="tabbtn bg-emerald-600 px-4 py-2 rounded-lg text-sm">از روی پلن</button>
    <button type="button" data-tab="template" class="tabbtn bg-card2 px-4 py-2 rounded-lg text-sm">از روی قالب</button>
    <?php if ($canCustom): ?><button type="button" data-tab="custom" class="tabbtn bg-card2 px-4 py-2 rounded-lg text-sm">سفارشی</button><?php endif; ?>
  </div>

  <form method="post" action="/panel/configs" class="bg-card border border-line rounded-xl p-4 space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="source" id="source" value="plan">

    <!-- PLAN -->
    <div data-pane="plan" class="space-y-2">
      <label class="block text-sm text-stone-300">انتخاب پلن</label>
      <?php if (!$plans): ?><p class="text-sm text-rose-400">پلنی فعال نیست.</p><?php endif; ?>
      <div class="space-y-2">
        <?php foreach ($plans as $p): ?>
          <label class="pick justify-between">
            <span class="flex items-center gap-2"><input type="radio" name="plan_id" value="<?= $p['id'] ?>"> <?= e($p['name']) ?></span>
            <span class="text-xs text-stone-400"><?= (int)$p['volume_gb'] ?>گیگ / <?= (int)$p['duration_days'] ?>روز — <span class="text-emerald-400"><?= toman((int)$p['price']) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TEMPLATE -->
    <div data-pane="template" class="space-y-2 hidden">
      <label class="block text-sm text-stone-300">انتخاب قالب</label>
      <?php if (!$templates): ?><p class="text-sm text-rose-400">قالبی تعریف نشده است.</p><?php endif; ?>
      <div class="space-y-2">
        <?php foreach ($templates as $t): ?>
          <label class="pick justify-between">
            <span class="flex items-center gap-2"><input type="radio" name="template_id" value="<?= $t['id'] ?>"> <?= e($t['name']) ?></span>
            <span class="text-xs text-stone-400"><?= (int)$t['volume_gb'] ?>گیگ / <?= (int)$t['duration_days'] ?>روز</span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CUSTOM -->
    <?php if ($canCustom): ?>
    <div data-pane="custom" class="space-y-3 hidden">
      <div class="grid grid-cols-2 gap-3">
        <div><label class="block text-xs text-stone-400 mb-1">حجم (گیگ)</label><input type="number" name="volume_gb" value="<?= old('volume_gb') ?>" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-stone-400 mb-1">مدت (روز)</label><input type="number" name="days" value="<?= old('days') ?>" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm"></div>
      </div>
      <div>
        <label class="block text-xs text-stone-400 mb-1">Squadها</label>
        <div class="grid grid-cols-2 gap-2">
          <?php foreach ($squads as $s): ?>
            <label class="pick"><input type="checkbox" name="squads[]" value="<?= e($s['uuid']) ?>"> <?= e($s['name']) ?></label>
          <?php endforeach; ?>
          <?php if (!$squads): ?><span class="text-xs text-rose-400">Squad مجازی در دسترس نیست.</span><?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <button class="w-full btn-brand py-2.5 rounded-lg font-semibold">ساخت و کسر از کیف پول</button>
  </form>
</div>

<script>
const btns = document.querySelectorAll('.tabbtn');
const panes = document.querySelectorAll('[data-pane]');
btns.forEach(b => b.addEventListener('click', () => {
  const tab = b.dataset.tab;
  document.getElementById('source').value = tab;
  btns.forEach(x => x.classList.replace('bg-emerald-600','bg-card2'));
  b.classList.replace('bg-card2','bg-emerald-600');
  panes.forEach(p => p.classList.toggle('hidden', p.dataset.pane !== tab));
}));
</script>
