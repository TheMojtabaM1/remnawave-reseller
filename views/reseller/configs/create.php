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
    <button type="button" data-tab="plan" class="tabbtn bg-brand px-4 py-2 rounded-lg text-sm">از روی پلن</button>
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
        <?php if (!empty($lockedNames)): ?>
          <div class="text-sm bg-card2 border border-line2 rounded-lg px-3 py-2">🔒 توسط مدیر قفل شده: <span class="text-brand-light"><?= e(implode('، ', $lockedNames)) ?></span></div>
        <?php else: ?>
        <div class="grid grid-cols-2 gap-2">
          <?php foreach ($squads as $s): ?>
            <label class="pick"><input type="checkbox" name="squads[]" value="<?= e($s['uuid']) ?>"> <?= e($s['name']) ?></label>
          <?php endforeach; ?>
          <?php if (!$squads): ?><span class="text-xs text-rose-400">Squad مجازی در دسترس نیست.</span><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($canCustomName)): ?>
    <div class="border-t border-line pt-3">
      <label class="block text-xs text-stone-400 mb-1">نام دلخواه کانفیگ (اختیاری)</label>
      <div class="flex items-center gap-2">
        <span class="text-stone-500 text-sm font-mono"><?= e($r['prefix']) ?>_</span>
        <input name="custom_name" value="<?= old('custom_name') ?>" maxlength="24" placeholder="مثلاً ali2024" pattern="[A-Za-z0-9]+" class="inp flex-1">
      </div>
      <div class="text-[11px] text-stone-500 mt-1">فقط حروف انگلیسی و عدد. خالی بگذارید تا نام تصادفی ساخته شود.</div>
    </div>
    <?php endif; ?>

    <div id="quoteBox" class="bg-card2 border border-line2 rounded-xl p-3 text-sm hidden">
      <div class="flex flex-wrap gap-x-6 gap-y-1">
        <span>حجم: <b id="qVol">—</b></span>
        <span>انقضا: <b id="qExp">—</b></span>
        <span>قیمت: <b id="qPrice" class="text-brand-light">—</b></span>
      </div>
      <div id="qWarn" class="text-rose-400 text-xs mt-1 hidden">⚠️ موجودی کیف پول کافی نیست.</div>
    </div>

    <button class="w-full btn-brand py-2.5 rounded-lg font-semibold">ساخت و کسر از کیف پول</button>
  </form>
</div>

<script>
const btns = document.querySelectorAll('.tabbtn');
const panes = document.querySelectorAll('[data-pane]');
btns.forEach(b => b.addEventListener('click', () => {
  const tab = b.dataset.tab;
  document.getElementById('source').value = tab;
  btns.forEach(x => x.classList.replace('bg-brand','bg-card2'));
  b.classList.replace('bg-card2','bg-brand');
  panes.forEach(p => p.classList.toggle('hidden', p.dataset.pane !== tab));
  refreshQuote();
}));

// Live price + expiry preview (#130/#131)
let qTimer;
function refreshQuote(){
  clearTimeout(qTimer);
  qTimer = setTimeout(async () => {
    const src = document.getElementById('source').value;
    const p = new URLSearchParams({source: src});
    if (src === 'plan')      { const el=document.querySelector('input[name=plan_id]:checked'); if(!el) return hideQuote(); p.set('plan_id', el.value); }
    else if (src === 'template'){ const el=document.querySelector('input[name=template_id]:checked'); if(!el) return hideQuote(); p.set('template_id', el.value); }
    else { const v=document.querySelector('input[name=volume_gb]')?.value, d=document.querySelector('input[name=days]')?.value; if(!v||!d) return hideQuote(); p.set('volume_gb',v); p.set('days',d); }
    try{
      const r = await fetch('/panel/configs/quote?'+p.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const j = await r.json();
      if(!j.ok) return hideQuote();
      document.getElementById('qVol').textContent = j.volume_gb + ' گیگ / ' + j.days + ' روز';
      document.getElementById('qExp').textContent = j.expires;
      document.getElementById('qPrice').textContent = j.price_label;
      document.getElementById('qWarn').classList.toggle('hidden', j.affordable);
      document.getElementById('quoteBox').classList.remove('hidden');
    }catch(e){ hideQuote(); }
  }, 250);
}
function hideQuote(){ document.getElementById('quoteBox').classList.add('hidden'); }
document.querySelectorAll('input[name=plan_id],input[name=template_id],input[name=volume_gb],input[name=days]').forEach(el=>{
  el.addEventListener('change', refreshQuote); el.addEventListener('input', refreshQuote);
});
</script>
