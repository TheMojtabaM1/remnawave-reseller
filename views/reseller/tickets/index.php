<?php
$st = ['open'=>['باز','bg-brand/20 text-brand-light'],'answered'=>['پاسخ داده‌شده','bg-emerald-600/20 text-emerald-400'],'closed'=>['بسته','bg-white/10 text-stone-300']];
$pr = ['low'=>'کم','normal'=>'عادی','high'=>'فوری'];
?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold">تیکت‌های پشتیبانی</h2>
  <a href="/panel/tickets/create" class="btn-brand px-4 py-2 rounded-lg text-sm">➕ تیکت جدید</a>
</div>

<div class="glass rounded-2xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="text-right p-3">#</th><th class="text-right p-3">موضوع</th><th class="text-right p-3">اولویت</th><th class="text-right p-3">پیام‌ها</th><th class="text-right p-3">وضعیت</th><th class="text-right p-3">آخرین به‌روزرسانی</th></tr></thead>
    <tbody>
    <?php foreach ($tickets as $t): [$slabel,$sclass] = $st[$t['status']] ?? ['—','']; ?>
      <tr class="border-t border-line row-hover">
        <td class="p-3 text-stone-400">#<?= $t['id'] ?></td>
        <td class="p-3"><a href="/panel/tickets/<?= $t['id'] ?>" class="text-brand hover:underline"><?= e($t['subject']) ?></a></td>
        <td class="p-3 text-xs"><?= e($pr[$t['priority']] ?? $t['priority']) ?></td>
        <td class="p-3"><?= (int)$t['msgs'] ?></td>
        <td class="p-3"><span class="<?= $sclass ?> px-2 py-1 rounded text-xs"><?= $slabel ?></span></td>
        <td class="p-3 text-xs text-stone-400"><?= shamsi($t['updated_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$tickets): ?><tr><td colspan="6" class="p-6 text-center text-stone-500">هنوز تیکتی ثبت نکرده‌اید.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
