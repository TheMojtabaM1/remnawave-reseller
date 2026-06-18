<?php
function node_val(array $n, array $keys, $def = null) {
    foreach ($keys as $k) { if (array_key_exists($k, $n) && $n[$k] !== null) return $n[$k]; }
    return $def;
}
?>
<?php if ($error): ?>
  <div class="bg-rose-600/20 text-rose-300 px-4 py-3 rounded-lg mb-4 text-sm">خطا در دریافت نودها: <?= e($error) ?></div>
<?php endif; ?>

<!-- Live monitor (#119) -->
<div class="glass rounded-2xl p-4 mb-4">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-bold flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span> مانیتور زنده</h3>
    <div class="flex items-center gap-4 text-sm">
      <span>کاربر آنلاین: <b id="liveOnline" class="text-brand">…</b></span>
      <span class="text-stone-400 text-xs">به‌روزرسانی هر ۵ ثانیه</span>
    </div>
  </div>
  <canvas id="liveChart" height="70"></canvas>
  <div id="liveNodes" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2 mt-3"></div>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
  <?php foreach ($nodes as $n):
    $online = (bool) node_val($n, ['isNodeOnline','isConnected','isOnline'], false);
    $name = node_val($n, ['name','nodeName'], 'بدون نام');
    $addr = node_val($n, ['address','host'], '');
    $usersOnline = node_val($n, ['usersOnline','onlineUsers'], null);
    $used = node_val($n, ['trafficUsedBytes','usedTrafficBytes'], null);
    $disabled = (bool) node_val($n, ['isDisabled'], false);
  ?>
    <div class="bg-card border border-line rounded-xl p-4">
      <div class="flex items-center justify-between">
        <div class="font-semibold"><?= e($name) ?></div>
        <span class="text-xs px-2 py-1 rounded <?= $disabled ? 'bg-white/10 text-stone-300' : ($online ? 'bg-emerald-600/20 text-emerald-400' : 'bg-rose-600/20 text-rose-400') ?>">
          <?= $disabled ? 'غیرفعال' : ($online ? '🟢 آنلاین' : '🔴 آفلاین') ?>
        </span>
      </div>
      <?php if ($addr): ?><div class="text-xs text-stone-500 mt-1 font-mono"><?= e($addr) ?></div><?php endif; ?>
      <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
        <?php if ($usersOnline !== null): ?><div><span class="text-stone-400 text-xs">کاربر آنلاین:</span> <?= (int)$usersOnline ?></div><?php endif; ?>
        <?php if ($used !== null): ?><div><span class="text-stone-400 text-xs">ترافیک:</span> <?= human_bytes((int)$used) ?></div><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$nodes && !$error): ?><div class="bg-card border border-line rounded-xl p-6 text-center text-stone-500 md:col-span-3">نودی یافت نشد.</div><?php endif; ?>
</div>

<script>
(function(){
  const ctx = document.getElementById('liveChart');
  const buf = { labels: [], data: [] };
  const chart = new Chart(ctx, {
    type:'line',
    data:{ labels:buf.labels, datasets:[{ label:'کاربران آنلاین', data:buf.data, borderColor:'#f97316', backgroundColor:'rgba(249,115,22,.15)', fill:true, tension:.35, pointRadius:0 }] },
    options:{ animation:false, plugins:{legend:{display:false}}, scales:{ x:{ticks:{color:'#a8a29e',maxTicksLimit:8}}, y:{beginAtZero:true,ticks:{color:'#a8a29e',precision:0}} } }
  });
  async function tick(){
    try{
      const r = await fetch('/owner/nodes/live', {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const j = await r.json();
      if(!j.ok) return;
      document.getElementById('liveOnline').textContent = j.online >= 0 ? j.online : j.nodeUsers;
      buf.labels.push(j.t); buf.data.push(j.online >= 0 ? j.online : j.nodeUsers);
      if(buf.labels.length > 30){ buf.labels.shift(); buf.data.shift(); }
      chart.update();
      document.getElementById('liveNodes').innerHTML = j.nodes.map(n =>
        `<div class="bg-card2 border border-line rounded-lg p-2 text-xs flex items-center justify-between">
          <span>${n.name}</span>
          <span class="${n.online?'text-emerald-400':'text-rose-400'}">${n.online?'🟢 '+n.users:'🔴'}</span>
        </div>`).join('');
    }catch(e){}
  }
  tick(); setInterval(tick, 5000);
})();
</script>
