<?php $periods = ['today'=>'امروز','week'=>'هفته','month'=>'ماه','all'=>'کل زمان']; ?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold">برترین نمایندگان</h2>
  <div class="flex gap-1">
    <?php foreach ($periods as $k=>$v): ?>
      <a href="?period=<?= $k ?>" class="px-3 py-1.5 rounded-lg text-sm <?= $period===$k?'bg-sky-600':'bg-slate-800' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-800/60 text-slate-300 text-xs"><tr><th class="text-right p-3">رتبه</th><th class="text-right p-3">نماینده</th><th class="text-right p-3">فروش</th><th class="text-right p-3">تعداد تراکنش</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): if ((int)$r['sales'] <= 0 && $i > 0) continue; $medal = ['🥇','🥈','🥉'][$i] ?? ($i+1); ?>
      <tr class="border-t border-slate-800">
        <td class="p-3 text-lg"><?= $medal ?></td>
        <td class="p-3"><a href="/owner/resellers/<?= $r['id'] ?>" class="text-sky-400"><?= e($r['display_name'] ?: $r['username']) ?></a></td>
        <td class="p-3 font-semibold"><?= toman((int)$r['sales']) ?></td>
        <td class="p-3"><?= (int)$r['tx_count'] ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="4" class="p-4 text-center text-slate-500">داده‌ای نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
