<div class="grid lg:grid-cols-3 gap-4">
  <div class="space-y-4">
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
      <h3 class="font-semibold mb-3">تهیه پشتیبان</h3>
      <form method="post" action="/owner/backups/create"><?= csrf_field() ?>
        <button class="w-full bg-emerald-600 hover:bg-emerald-500 py-2 rounded-lg text-sm">💾 تهیه پشتیبان جدید</button>
      </form>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
      <h3 class="font-semibold mb-3">بازیابی از فایل</h3>
      <form method="post" action="/owner/backups/restore" enctype="multipart/form-data" onsubmit="return confirm('بازیابی، داده‌های فعلی را بازنویسی می‌کند. ادامه؟')" class="space-y-3">
        <?= csrf_field() ?>
        <input type="file" name="backup" accept=".sql,.gz" required class="w-full text-sm text-slate-400 file:bg-slate-800 file:border-0 file:px-3 file:py-2 file:rounded-lg file:text-slate-200">
        <button class="w-full bg-rose-600 hover:bg-rose-500 py-2 rounded-lg text-sm">♻️ بازیابی</button>
      </form>
      <p class="text-[11px] text-slate-500 mt-2">فقط فایل‌های .sql / .sql.gz معتبرند.</p>
    </div>
  </div>

  <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">پشتیبان‌های موجود</h3>
    <table class="w-full text-sm">
      <thead class="text-slate-400 text-xs"><tr><th class="text-right p-2">نام فایل</th><th class="text-right p-2">حجم</th><th class="text-right p-2">تاریخ (UTC)</th><th class="text-right p-2">دانلود</th></tr></thead>
      <tbody>
      <?php foreach ($backups as $b): ?>
        <tr class="border-t border-slate-800">
          <td class="p-2 font-mono text-xs"><?= e($b['name']) ?></td>
          <td class="p-2"><?= human_bytes($b['size']) ?></td>
          <td class="p-2 text-xs"><?= e($b['mtime']) ?></td>
          <td class="p-2"><a href="/owner/backups/download?file=<?= urlencode($b['name']) ?>" class="text-sky-400 text-xs">دانلود</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$backups): ?><tr><td colspan="4" class="p-4 text-center text-slate-500">پشتیبانی موجود نیست.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
