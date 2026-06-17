<?php
$sev = ['info'=>'bg-sky-600','warning'=>'bg-amber-600','critical'=>'bg-rose-600'];
$typeLabels = ['low_balance'=>'موجودی کم','node_down'=>'نود قطع','traffic_spike'=>'جهش ترافیک','reseller_suspended'=>'تعلیق نماینده'];
?>
<div class="flex items-center justify-between mb-4">
  <h2 class="text-lg font-semibold">هشدارها</h2>
  <form method="post" action="/owner/alerts/read-all"><?= csrf_field() ?><button class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm">علامت‌گذاری همه به‌عنوان خوانده‌شده</button></form>
</div>

<div class="space-y-2">
  <?php foreach ($alerts as $a): ?>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex items-center justify-between gap-3 <?= $a['is_read'] ? 'opacity-60' : '' ?>">
      <div class="flex items-center gap-3">
        <span class="<?= $sev[$a['severity']] ?? 'bg-slate-700' ?> text-white text-xs px-2 py-1 rounded"><?= e($typeLabels[$a['type']] ?? $a['type']) ?></span>
        <div>
          <div class="text-sm"><?= e($a['message']) ?></div>
          <div class="text-xs text-slate-500"><?= jdate($a['created_at']) ?></div>
        </div>
      </div>
      <?php if (!$a['is_read']): ?>
        <form method="post" action="/owner/alerts/<?= $a['id'] ?>/read"><?= csrf_field() ?><button class="text-xs text-sky-400 hover:underline">خوانده شد</button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$alerts): ?><div class="bg-slate-900 border border-slate-800 rounded-xl p-6 text-center text-slate-500">هشداری وجود ندارد. ✅</div><?php endif; ?>
</div>
