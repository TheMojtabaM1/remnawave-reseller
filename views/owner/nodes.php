<?php
function node_val(array $n, array $keys, $def = null) {
    foreach ($keys as $k) { if (array_key_exists($k, $n) && $n[$k] !== null) return $n[$k]; }
    return $def;
}
?>
<?php if ($error): ?>
  <div class="bg-rose-600/20 text-rose-300 px-4 py-3 rounded-lg mb-4 text-sm">خطا در دریافت نودها: <?= e($error) ?></div>
<?php endif; ?>

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
