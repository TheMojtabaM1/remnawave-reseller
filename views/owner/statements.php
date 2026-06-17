<div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-4">
  <h3 class="font-semibold mb-3">تولید صورتحساب ماهانه</h3>
  <form method="post" action="/owner/statements/generate" class="flex flex-wrap gap-2 items-end">
    <?= csrf_field() ?>
    <div><label class="block text-xs text-slate-400 mb-1">دوره (YYYY-MM)</label>
      <input name="period" value="<?= e($defaultPeriod) ?>" pattern="\d{4}-\d{2}" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm"></div>
    <button class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm">تولید برای همه نمایندگان</button>
  </form>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-800/60 text-slate-300 text-xs"><tr><th class="text-right p-2">دوره</th><th class="text-right p-2">نماینده</th><th class="text-right p-2">مانده ابتدا</th><th class="text-right p-2">مانده پایان</th><th class="text-right p-2">فروش</th><th class="text-right p-2">بازگشت</th><th class="text-right p-2">کانفیگ</th><th class="text-right p-2">دانلود</th></tr></thead>
    <tbody>
    <?php foreach ($statements as $s): ?>
      <tr class="border-t border-slate-800">
        <td class="p-2"><?= e($s['period']) ?></td>
        <td class="p-2"><?= e($s['display_name'] ?: $s['username']) ?></td>
        <td class="p-2"><?= toman((int)$s['opening_balance']) ?></td>
        <td class="p-2"><?= toman((int)$s['closing_balance']) ?></td>
        <td class="p-2"><?= toman((int)$s['total_sales']) ?></td>
        <td class="p-2"><?= toman((int)$s['total_refunds']) ?></td>
        <td class="p-2"><?= (int)$s['configs_count'] ?></td>
        <td class="p-2"><?php if ($s['pdf_path']): ?><a href="/owner/statements/<?= $s['id'] ?>/download" class="text-sky-400 text-xs">PDF</a><?php else: ?>—<?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$statements): ?><tr><td colspan="8" class="p-4 text-center text-slate-500">صورتحسابی تولید نشده است.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
