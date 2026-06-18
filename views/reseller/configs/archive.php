<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold">🗄️ بایگانی کانفیگ‌های حذف‌شده</h2>
  <a href="/panel/configs" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm">بازگشت به کانفیگ‌ها</a>
</div>

<div class="glass rounded-2xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="text-right p-3">یوزرنیم</th><th class="text-right p-3">حجم</th><th class="text-right p-3">مصرف نهایی</th><th class="text-right p-3">قیمت</th><th class="text-right p-3">ساخت</th></tr></thead>
    <tbody>
    <?php foreach ($configs as $c): ?>
      <tr class="border-t border-line">
        <td class="p-3 font-mono text-xs"><?= e($c['remnawave_username']) ?></td>
        <td class="p-3"><?= (int)$c['volume_gb'] ?>گیگ</td>
        <td class="p-3"><?= human_bytes((int)$c['last_used_bytes']) ?></td>
        <td class="p-3"><?= toman((int)$c['price_charged']) ?></td>
        <td class="p-3 text-xs text-stone-400"><?= shamsi($c['created_at'],'date') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$configs): ?><tr><td colspan="5" class="p-6 text-center text-stone-500">بایگانی خالی است.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p class="text-xs text-stone-500 mt-3">برای بازیابی یک کانفیگ حذف‌شده با مدیر تماس بگیرید (بازیابی نیازمند ساخت مجدد روی سرور است).</p>
