<?php
$cards = [
    ['درآمد کل (فروش)', toman($kpis['total_revenue']), 'bg-emerald-600'],
    ['بازگشت وجه', toman($kpis['total_refunds']), 'bg-amber-600'],
    ['کانفیگ‌های فعال', number_format($kpis['active_configs']) . ' / ' . number_format($kpis['total_configs']), 'bg-brand'],
    ['نمایندگان فعال', number_format($kpis['active_resellers']), 'bg-violet-600'],
    ['ترافیک فروخته‌شده', number_format($kpis['traffic_sold_gb']) . ' گیگ', 'bg-cyan-600'],
    ['ترافیک مصرف‌شده', number_format($kpis['traffic_used_gb']) . ' گیگ', 'bg-rose-600'],
];
?>
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
  <?php foreach ($cards as [$label, $value, $color]): ?>
    <div class="<?= $color ?> rounded-xl p-4 shadow">
      <div class="text-xs text-white/80"><?= e($label) ?></div>
      <div class="text-xl font-bold mt-1"><?= e($value) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">درآمد ۳۰ روز اخیر</h3>
    <canvas id="revChart" height="110"></canvas>
  </div>

  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">هشدارهای فعال</h3>
    <?php if (!$alerts): ?>
      <p class="text-sm text-stone-500">هشداری وجود ندارد. ✅</p>
    <?php else: foreach ($alerts as $a): ?>
      <div class="text-sm border-b border-line py-2">
        <span class="text-amber-400">●</span> <?= e($a['message']) ?>
        <div class="text-xs text-stone-500"><?= jdate($a['created_at']) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">برترین نمایندگان این ماه</h3>
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right py-1">نماینده</th><th class="text-right">فروش</th></tr></thead>
      <tbody>
      <?php foreach ($topResellers as $r): ?>
        <tr class="border-t border-line"><td class="py-2"><?= e($r['display_name'] ?: $r['username']) ?></td><td><?= toman((int) $r['sales']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topResellers): ?><tr><td colspan="2" class="text-stone-500 py-2">داده‌ای نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">فعالیت‌های اخیر</h3>
    <div class="space-y-1 text-sm max-h-72 overflow-y-auto">
      <?php foreach ($recent as $a): ?>
        <div class="border-b border-line py-1.5 flex justify-between">
          <span><span class="text-stone-400"><?= e($a['actor_name'] ?? $a['actor_type']) ?></span> — <?= e($a['action']) ?></span>
          <span class="text-xs text-stone-500"><?= jdate($a['created_at'], 'time') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('revChart'), {
  type: 'line',
  data: { labels: <?= json_encode($series['labels'], JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ label: 'درآمد (تومان)', data: <?= json_encode($series['data']) ?>,
      borderColor: '#34d399', backgroundColor: 'rgba(52,211,153,.15)', fill: true, tension: .3 }] },
  options: { plugins: { legend: { labels: { color: '#cbd5e1' } } },
    scales: { x: { ticks: { color: '#94a3b8' } }, y: { ticks: { color: '#94a3b8' } } } }
});
</script>
