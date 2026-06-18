<div class="flex items-center justify-between mb-4">
  <h2 class="text-lg font-semibold">گزارش‌های مالی</h2>
  <a href="/owner/reports/export" class="btn-brand px-4 py-2 rounded-lg text-sm">📊 خروجی Excel</a>
</div>

<div class="grid md:grid-cols-4 gap-3 mb-4">
  <div class="bg-emerald-600 rounded-xl p-4"><div class="text-xs text-white/80">درآمد کل</div><div class="text-lg font-bold"><?= toman($kpis['total_revenue']) ?></div></div>
  <div class="bg-amber-600 rounded-xl p-4"><div class="text-xs text-white/80">بازگشت وجه</div><div class="text-lg font-bold"><?= toman($kpis['total_refunds']) ?></div></div>
  <div class="bg-brand rounded-xl p-4"><div class="text-xs text-white/80">کانفیگ کل</div><div class="text-lg font-bold"><?= number_format($kpis['total_configs']) ?></div></div>
  <div class="bg-violet-600 rounded-xl p-4"><div class="text-xs text-white/80">ترافیک فروخته‌شده</div><div class="text-lg font-bold"><?= number_format($kpis['traffic_sold_gb']) ?> گیگ</div></div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-4">
  <div class="bg-card border border-line rounded-xl p-4"><h3 class="font-semibold mb-3">روند درآمد</h3><canvas id="revChart" height="120"></canvas></div>
  <div class="bg-card border border-line rounded-xl p-4"><h3 class="font-semibold mb-3">پلن‌های پرفروش</h3><canvas id="planChart" height="120"></canvas></div>
</div>

<div class="bg-card border border-line rounded-xl p-4">
  <h3 class="font-semibold mb-3">تفکیک فروش هر نماینده</h3>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right p-2">نماینده</th><th class="text-right p-2">موجودی</th><th class="text-right p-2">کل فروش</th><th class="text-right p-2">بازگشت وجه</th><th class="text-right p-2">فروش خالص</th><th class="text-right p-2">کانفیگ</th></tr></thead>
      <tbody>
      <?php foreach ($perReseller as $row): $net = (int)$row['total_sales'] - (int)$row['total_refunds']; ?>
        <tr class="border-t border-line">
          <td class="p-2"><a href="/owner/resellers/<?= $row['id'] ?>" class="text-brand"><?= e($row['display_name'] ?: $row['username']) ?></a></td>
          <td class="p-2 <?= (int)$row['balance']<0?'text-rose-400':'' ?>"><?= toman((int)$row['balance']) ?></td>
          <td class="p-2"><?= toman((int)$row['total_sales']) ?></td>
          <td class="p-2"><?= toman((int)$row['total_refunds']) ?></td>
          <td class="p-2 font-semibold"><?= toman($net) ?></td>
          <td class="p-2"><?= (int)$row['configs_count'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$perReseller): ?><tr><td colspan="6" class="p-4 text-center text-stone-500">داده‌ای نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
new Chart(document.getElementById('revChart'), {
  type:'bar',
  data:{labels:<?= json_encode($series['labels'], JSON_UNESCAPED_UNICODE) ?>,datasets:[{label:'درآمد',data:<?= json_encode($series['data']) ?>,backgroundColor:'#38bdf8'}]},
  options:{plugins:{legend:{labels:{color:'#cbd5e1'}}},scales:{x:{ticks:{color:'#94a3b8'}},y:{ticks:{color:'#94a3b8'}}}}
});
new Chart(document.getElementById('planChart'), {
  type:'doughnut',
  data:{labels:<?= json_encode(array_map(fn($p)=>$p['name'],$topPlans), JSON_UNESCAPED_UNICODE) ?>,datasets:[{data:<?= json_encode(array_map(fn($p)=>(int)$p['sold'],$topPlans)) ?>,backgroundColor:['#34d399','#38bdf8','#a78bfa','#f59e0b','#f43f5e','#22d3ee','#84cc16','#e879f9','#fb923c','#60a5fa']}]},
  options:{plugins:{legend:{labels:{color:'#cbd5e1'},position:'right'}}}
});
</script>
