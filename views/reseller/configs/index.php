<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold">کانفیگ‌های من</h2>
  <?php if ($perm('can_create_config')): ?><a href="/panel/configs/create" class="btn-brand px-4 py-2 rounded-lg text-sm">➕ ساخت کانفیگ</a><?php endif; ?>
</div>

<form method="get" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="<?= e($q) ?>" placeholder="جستجو یوزرنیم…" class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
  <select name="status" class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
    <option value="">همه وضعیت‌ها</option>
    <option value="active" <?= $status==='active'?'selected':'' ?>>فعال</option>
    <option value="disabled" <?= $status==='disabled'?'selected':'' ?>>غیرفعال</option>
    <option value="expired" <?= $status==='expired'?'selected':'' ?>>منقضی</option>
  </select>
  <button class="bg-brand hover:bg-brand-light px-4 py-2 rounded-lg text-sm">فیلتر</button>
</form>

<form method="post" action="/panel/configs/bulk">
  <?= csrf_field() ?>
  <div data-selbar class="bulkbar opacity-50 flex flex-wrap gap-2 items-center p-3 mb-3 transition">
    <label class="pick !py-1.5"><input type="checkbox" onchange="selectAll(this)"> انتخاب همه</label>
    <span class="text-xs text-stone-400">انتخاب‌شده: <b data-selcount class="text-brand">0</b></span>
    <select name="operation" class="inp !w-auto mr-auto">
      <?php if ($perm('can_edit_config')): ?><option value="enable">فعال‌سازی</option><option value="disable">غیرفعال‌سازی</option><?php endif; ?>
      <?php if ($perm('can_regenerate_subscription')): ?><option value="regenerate">بازتولید لینک</option><?php endif; ?>
      <?php if ($perm('can_delete_config')): ?><option value="delete">حذف (با بازگشت وجه)</option><?php endif; ?>
    </select>
    <button onclick="if(!document.querySelector('.cb:checked')){alert('موردی انتخاب نشده');return false;}return confirm('اعمال روی موارد انتخاب‌شده؟')" class="btn-brand px-5 py-2 rounded-lg text-sm">اعمال گروهی</button>
  </div>

  <div class="bg-card border border-line rounded-xl overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="p-2"></th><th class="text-right p-2">یوزرنیم</th><th class="text-right p-2">حجم</th><th class="text-right p-2">مصرف</th><th class="text-right p-2">انقضا</th><th class="text-right p-2">قیمت</th><th class="text-right p-2">وضعیت</th></tr></thead>
      <tbody>
      <?php foreach ($configs as $c):
        $usedPct = (int)$c['volume_gb'] > 0 ? min(100, round(bytes_to_gb((int)$c['last_used_bytes']) / (int)$c['volume_gb'] * 100)) : 0; ?>
        <tr class="border-t border-line hover:bg-card2/30">
          <td class="p-2"><input type="checkbox" class="cb" name="config_ids[]" value="<?= $c['id'] ?>"></td>
          <td class="p-2 font-mono text-xs"><a href="/panel/configs/<?= $c['id'] ?>" class="text-brand"><?= e($c['remnawave_username']) ?></a></td>
          <td class="p-2"><?= (int)$c['volume_gb'] ?>گیگ</td>
          <td class="p-2">
            <div class="text-xs"><?= human_bytes((int)$c['last_used_bytes']) ?> (<?= $usedPct ?>٪)</div>
            <div class="h-1 bg-white/10 rounded mt-1"><div class="h-1 bg-emerald-500 rounded" style="width:<?= $usedPct ?>%"></div></div>
          </td>
          <td class="p-2 text-xs"><?= jdate($c['expires_at'],'date') ?></td>
          <td class="p-2 text-xs"><?= toman((int)$c['price_charged']) ?></td>
          <td class="p-2"><?php
            $badge = ['active'=>'bg-emerald-600/20 text-emerald-400','disabled'=>'bg-white/10 text-stone-300','expired'=>'bg-rose-600/20 text-rose-400'][$c['status']] ?? 'bg-white/10';
            $label = ['active'=>'فعال','disabled'=>'غیرفعال','expired'=>'منقضی'][$c['status']] ?? $c['status']; ?>
            <span class="<?= $badge ?> px-2 py-1 rounded text-xs"><?= $label ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$configs): ?><tr><td colspan="7" class="p-6 text-center text-stone-500">کانفیگی یافت نشد.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</form>

<?php if ($pages > 1): ?>
<div class="flex justify-center gap-1 mt-4 text-sm flex-wrap">
  <?php for ($i = max(1,$page-3); $i <= min($pages,$page+3); $i++): ?>
    <a href="?q=<?= e($q) ?>&status=<?= e($status) ?>&page=<?= $i ?>" class="px-3 py-1 rounded <?= $i===$page?'bg-brand':'bg-card2' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
