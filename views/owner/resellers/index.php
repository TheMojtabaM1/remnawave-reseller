<div class="flex items-center justify-between mb-4">
  <h2 class="text-lg font-semibold">فهرست نمایندگان</h2>
  <a href="/owner/resellers/create" class="bg-brand hover:bg-brand-light px-4 py-2 rounded-lg text-sm">➕ افزودن نماینده</a>
</div>

<div class="bg-card border border-line rounded-xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-card2/60 text-stone-300 text-xs">
      <tr>
        <th class="text-right p-3">نماینده</th>
        <th class="text-right p-3">پیشوند</th>
        <th class="text-right p-3">موجودی</th>
        <th class="text-right p-3">کانفیگ‌ها</th>
        <th class="text-right p-3">وضعیت</th>
        <th class="text-right p-3">عملیات</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($resellers as $r): ?>
      <tr class="border-t border-line hover:bg-card2/30">
        <td class="p-3">
          <a href="/owner/resellers/<?= $r['id'] ?>" class="text-brand hover:underline"><?= e($r['display_name'] ?: $r['username']) ?></a>
          <div class="text-xs text-stone-500"><?= e($r['username']) ?></div>
        </td>
        <td class="p-3 text-stone-400"><?= e($r['prefix']) ?></td>
        <td class="p-3 <?= (int)$r['balance'] < 0 ? 'text-rose-400' : 'text-emerald-400' ?>"><?= toman((int)$r['balance']) ?></td>
        <td class="p-3"><?= number_format((int)$r['configs_count']) ?></td>
        <td class="p-3">
          <?php if ($r['status'] === 'active'): ?>
            <span class="bg-emerald-600/20 text-emerald-400 px-2 py-1 rounded text-xs">فعال</span>
          <?php else: ?>
            <span class="bg-rose-600/20 text-rose-400 px-2 py-1 rounded text-xs">معلق</span>
          <?php endif; ?>
        </td>
        <td class="p-3 flex gap-2">
          <a href="/owner/resellers/<?= $r['id'] ?>/edit" class="text-amber-400 hover:underline text-xs">ویرایش</a>
          <form method="post" action="/owner/resellers/<?= $r['id'] ?>/status" onsubmit="return confirm('تغییر وضعیت نماینده؟')">
            <?= csrf_field() ?>
            <button class="text-xs <?= $r['status'] === 'active' ? 'text-rose-400' : 'text-emerald-400' ?>"><?= $r['status'] === 'active' ? 'تعلیق' : 'فعال‌سازی' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$resellers): ?>
      <tr><td colspan="6" class="p-6 text-center text-stone-500">هنوز نماینده‌ای ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
