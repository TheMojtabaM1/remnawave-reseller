<?php
$st = ['open'=>['باز','bg-brand/20 text-brand-light'],'answered'=>['پاسخ داده‌شده','bg-emerald-600/20 text-emerald-400'],'closed'=>['بسته','bg-white/10 text-stone-300']];
$pr = ['low'=>'کم','normal'=>'عادی','high'=>'فوری'];
$tabs = ['' => 'همه', 'open' => 'باز', 'answered' => 'پاسخ‌داده', 'closed' => 'بسته'];
?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold">تیکت‌های پشتیبانی <?php if ($openCount): ?><span class="bg-brand text-white text-xs rounded-full px-2 py-0.5 mr-1"><?= $openCount ?> باز</span><?php endif; ?></h2>
  <div class="flex gap-1">
    <?php foreach ($tabs as $k => $v): ?>
      <a href="?status=<?= $k ?>" class="px-3 py-1.5 rounded-lg text-sm <?= $status===$k?'bg-brand text-white':'bg-white/10 text-stone-300' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="glass rounded-2xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="text-right p-3">#</th><th class="text-right p-3">نماینده</th><th class="text-right p-3">موضوع</th><th class="text-right p-3">اولویت</th><th class="text-right p-3">وضعیت</th><th class="text-right p-3">به‌روزرسانی</th></tr></thead>
    <tbody>
    <?php foreach ($tickets as $t): [$slabel,$sclass] = $st[$t['status']] ?? ['—','']; ?>
      <tr class="border-t border-line row-hover">
        <td class="p-3 text-stone-400">#<?= $t['id'] ?></td>
        <td class="p-3 text-xs"><?= e($t['display_name'] ?: $t['username']) ?></td>
        <td class="p-3"><a href="/owner/tickets/<?= $t['id'] ?>" class="text-brand hover:underline"><?= e($t['subject']) ?></a></td>
        <td class="p-3 text-xs <?= $t['priority']==='high'?'text-rose-400':'' ?>"><?= e($pr[$t['priority']] ?? $t['priority']) ?></td>
        <td class="p-3"><span class="<?= $sclass ?> px-2 py-1 rounded text-xs"><?= $slabel ?></span></td>
        <td class="p-3 text-xs text-stone-400"><?= shamsi($t['updated_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$tickets): ?><tr><td colspan="6" class="p-6 text-center text-stone-500">تیکتی نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
