<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
  <div class="glass glass-hover rounded-2xl p-4"><div class="text-xs text-stone-400">کل فروش</div><div class="text-xl font-extrabold text-gradient mt-1"><?= toman($kpis['total_sales']) ?></div></div>
  <div class="glass glass-hover rounded-2xl p-4"><div class="text-xs text-stone-400">بازگشت وجه</div><div class="text-xl font-bold text-amber-400 mt-1"><?= toman($kpis['total_refund']) ?></div></div>
  <div class="glass glass-hover rounded-2xl p-4"><div class="text-xs text-stone-400">کانفیگ فعال</div><div class="text-xl font-bold mt-1"><?= number_format($kpis['active']) ?> / <?= number_format($kpis['total']) ?></div></div>
  <div class="glass glass-hover rounded-2xl p-4"><div class="text-xs text-stone-400">ترافیک مصرف‌شده</div><div class="text-xl font-bold mt-1"><?= number_format($kpis['used_gb']) ?> / <?= number_format($kpis['sold_gb']) ?> گیگ</div></div>
</div>

<div class="glass rounded-2xl p-4 mb-4">
  <h3 class="font-bold mb-3">روند فروش ۳۰ روز اخیر</h3>
  <canvas id="rc" height="90"></canvas>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="glass rounded-2xl p-4">
    <h3 class="font-bold mb-3">پرمصرف‌ترین کانفیگ‌ها</h3>
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right p-2">یوزرنیم</th><th class="text-right p-2">مصرف</th><th class="text-right p-2">حجم</th></tr></thead>
      <tbody>
      <?php foreach ($topConfigs as $c): ?>
        <tr class="border-t border-line">
          <td class="p-2 font-mono text-xs"><?= e($c['remnawave_username']) ?></td>
          <td class="p-2"><?= human_bytes((int)$c['last_used_bytes']) ?></td>
          <td class="p-2"><?= (int)$c['volume_gb'] ?>گیگ</td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$topConfigs): ?><tr><td colspan="3" class="p-4 text-center text-stone-500">داده‌ای نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="glass rounded-2xl p-4">
    <h3 class="font-bold mb-3">فروش به تفکیک پلن</h3>
    <table class="w-full text-sm">
      <thead class="text-stone-400 text-xs"><tr><th class="text-right p-2">پلن</th><th class="text-right p-2">تعداد</th><th class="text-right p-2">درآمد</th></tr></thead>
      <tbody>
      <?php foreach ($byPlan as $p): ?>
        <tr class="border-t border-line">
          <td class="p-2"><?= e($p['name']) ?></td>
          <td class="p-2"><?= (int)$p['cnt'] ?></td>
          <td class="p-2"><?= toman((int)$p['revenue']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$byPlan): ?><tr><td colspan="3" class="p-4 text-center text-stone-500">داده‌ای نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
new Chart(document.getElementById('rc'), {
  type:'line',
  data:{labels:<?= json_encode($series['labels'], JSON_UNESCAPED_UNICODE) ?>,datasets:[{label:'فروش (تومان)',data:<?= json_encode($series['data']) ?>,borderColor:'#f97316',backgroundColor:'rgba(249,115,22,.15)',fill:true,tension:.35,pointRadius:0}]},
  options:{plugins:{legend:{labels:{color:'#d6d3d1'}}},scales:{x:{ticks:{color:'#a8a29e',maxTicksLimit:8}},y:{ticks:{color:'#a8a29e'}}}}
});
</script>
