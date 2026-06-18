<div class="bg-card border border-line rounded-xl p-4 mb-4">
  <form method="get" class="flex flex-wrap gap-2 items-end">
    <div><label class="block text-xs text-stone-400 mb-1">نوع کاربر</label>
      <select name="actor" class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <option value="">همه</option>
        <option value="owner" <?= $actor==='owner'?'selected':'' ?>>مدیر</option>
        <option value="reseller" <?= $actor==='reseller'?'selected':'' ?>>نماینده</option>
      </select>
    </div>
    <div><label class="block text-xs text-stone-400 mb-1">عملیات</label>
      <input name="action" value="<?= e($action) ?>" placeholder="مثلاً config.create" class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
    </div>
    <button class="bg-brand hover:bg-brand-light px-4 py-2 rounded-lg text-sm">فیلتر</button>
  </form>
</div>

<div class="bg-card border border-line rounded-xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="text-right p-2">تاریخ</th><th class="text-right p-2">کاربر</th><th class="text-right p-2">عملیات</th><th class="text-right p-2">هدف</th><th class="text-right p-2">جزئیات</th><th class="text-right p-2">IP</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr class="border-t border-line">
        <td class="p-2 text-xs whitespace-nowrap"><?= jdate($l['created_at']) ?></td>
        <td class="p-2 text-xs"><?= e($l['actor_type']) ?>#<?= (int)$l['actor_id'] ?></td>
        <td class="p-2"><span class="bg-card2 px-2 py-1 rounded text-xs"><?= e($l['action']) ?></span></td>
        <td class="p-2 text-xs text-stone-400"><?= e($l['target_type']) ?> <?= e($l['target_id']) ?></td>
        <td class="p-2 text-xs text-stone-500 max-w-xs truncate"><?= e($l['details']) ?></td>
        <td class="p-2 text-xs text-stone-500"><?= e($l['ip']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="6" class="p-4 text-center text-stone-500">رکوردی نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<div class="flex justify-center gap-1 mt-4 text-sm flex-wrap">
  <?php for ($i = max(1,$page-3); $i <= min($pages,$page+3); $i++): ?>
    <a href="?actor=<?= e($actor) ?>&action=<?= e($action) ?>&page=<?= $i ?>" class="px-3 py-1 rounded <?= $i===$page?'bg-brand':'bg-card2' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
