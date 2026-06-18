<div class="grid lg:grid-cols-3 gap-4 mb-4">
  <div class="bg-card border border-line rounded-xl p-4 lg:col-span-1 h-fit">
    <h3 class="font-semibold mb-3">ساخت گروهی کانفیگ</h3>
    <form method="post" action="/owner/bulk/create" class="space-y-3">
      <?= csrf_field() ?>
      <select name="reseller_id" required class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <option value="">انتخاب نماینده…</option>
        <?php foreach ($resellers as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['display_name'] ?: $r['username']) ?></option><?php endforeach; ?>
      </select>
      <select name="source" required class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <optgroup label="پلن‌ها">
          <?php foreach ($plans as $p): ?><option value="plan:<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= (int)$p['volume_gb'] ?>گیگ/<?= (int)$p['duration_days'] ?>روز)</option><?php endforeach; ?>
        </optgroup>
        <optgroup label="قالب‌ها">
          <?php foreach ($templates as $t): ?><option value="template:<?= $t['id'] ?>"><?= e($t['name']) ?> (<?= (int)$t['volume_gb'] ?>گیگ/<?= (int)$t['duration_days'] ?>روز)</option><?php endforeach; ?>
        </optgroup>
      </select>
      <input type="number" name="count" min="1" max="100" value="5" class="w-full bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
      <button class="w-full btn-brand py-2 rounded-lg text-sm">ساخت گروهی</button>
      <p class="text-[11px] text-stone-500">هزینه از کیف پول نماینده کسر می‌شود؛ در صورت کمبود موجودی، عملیات متوقف می‌شود.</p>
    </form>
  </div>

  <div class="lg:col-span-2 bg-card border border-line rounded-xl p-4">
    <h3 class="font-semibold mb-3">عملیات گروهی روی کانفیگ‌ها</h3>
    <form method="post" action="/owner/bulk/action">
      <?= csrf_field() ?>
      <div class="flex flex-wrap gap-2 items-center mb-3">
        <select name="operation" required class="bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
          <option value="enable">فعال‌سازی</option>
          <option value="disable">غیرفعال‌سازی</option>
          <option value="regenerate">بازتولید لینک</option>
          <option value="extend">تمدید مدت (بدون هزینه)</option>
          <option value="delete">حذف (با بازگشت وجه حجمی)</option>
        </select>
        <input type="number" name="extend_days" value="30" title="روز تمدید" class="w-24 bg-card2 border border-line2 rounded-lg px-3 py-2 text-sm">
        <button onclick="return confirm('اعمال عملیات روی موارد انتخاب‌شده؟')" class="bg-brand hover:bg-brand-light px-4 py-2 rounded-lg text-sm">اعمال</button>
        <label class="text-xs text-stone-400 mr-auto"><input type="checkbox" onclick="document.querySelectorAll('.cb').forEach(c=>c.checked=this.checked)"> انتخاب همه</label>
      </div>
      <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="text-stone-400 text-xs sticky top-0 bg-card"><tr><th class="p-2"></th><th class="text-right p-2">یوزرنیم</th><th class="text-right p-2">نماینده</th><th class="text-right p-2">حجم</th><th class="text-right p-2">انقضا</th><th class="text-right p-2">وضعیت</th></tr></thead>
          <tbody>
          <?php foreach ($configs as $c): ?>
            <tr class="border-t border-line">
              <td class="p-2"><input type="checkbox" class="cb" name="config_ids[]" value="<?= $c['id'] ?>"></td>
              <td class="p-2 font-mono text-xs"><?= e($c['remnawave_username']) ?></td>
              <td class="p-2 text-xs"><?= e($c['reseller_username']) ?></td>
              <td class="p-2"><?= (int)$c['volume_gb'] ?>گیگ</td>
              <td class="p-2 text-xs"><?= jdate($c['expires_at'],'date') ?></td>
              <td class="p-2 text-xs"><?= e($c['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$configs): ?><tr><td colspan="6" class="p-4 text-center text-stone-500">کانفیگی نیست.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>
