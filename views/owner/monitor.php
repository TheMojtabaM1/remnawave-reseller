<?php if ($error): ?>
  <div class="bg-rose-600/20 text-rose-300 px-4 py-3 rounded-lg mb-4 text-sm">خطا در دریافت داده از Remnawave: <?= e($error) ?></div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-3 mb-4">
  <div class="bg-emerald-600 rounded-xl p-5">
    <div class="text-sm text-white/80">کاربران آنلاین کل</div>
    <div class="text-3xl font-bold mt-1"><?= $onlineTotal >= 0 ? number_format($onlineTotal) : '—' ?></div>
  </div>
  <div class="bg-sky-600 rounded-xl p-5">
    <div class="text-sm text-white/80">نمایندگان دارای کاربر آنلاین</div>
    <div class="text-3xl font-bold mt-1"><?= count($perReseller) ?></div>
  </div>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
  <h3 class="font-semibold mb-3">کاربران آنلاین به تفکیک نماینده</h3>
  <table class="w-full text-sm">
    <thead class="text-slate-400 text-xs"><tr><th class="text-right p-2">نماینده</th><th class="text-right p-2">آنلاین</th></tr></thead>
    <tbody>
    <?php foreach ($perReseller as $rid => $count): $r = $resellers[$rid] ?? null; ?>
      <tr class="border-t border-slate-800">
        <td class="p-2"><?php if ($r): ?><a href="/owner/resellers/<?= $rid ?>" class="text-sky-400"><?= e($r['display_name'] ?: $r['username']) ?></a><?php else: ?>#<?= $rid ?><?php endif; ?></td>
        <td class="p-2"><span class="bg-emerald-600/20 text-emerald-400 px-2 py-1 rounded text-xs"><?= (int)$count ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$perReseller): ?><tr><td colspan="2" class="p-4 text-center text-slate-500">در حال حاضر کاربر آنلاینی شناسایی نشد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
