<?php $typeLabels = ['topup'=>'شارژ','charge'=>'خرید/تمدید','refund'=>'بازگشت وجه','manual_adjust'=>'اصلاح','gift'=>'هدیه']; ?>
<div class="grid md:grid-cols-3 gap-3 mb-4">
  <div class="<?= (int)$r['balance']<0?'bg-rose-600':'bg-emerald-600' ?> rounded-xl p-5 md:col-span-1">
    <div class="text-sm text-white/80">موجودی فعلی</div>
    <div class="text-2xl font-bold mt-1"><?= toman((int)$r['balance']) ?></div>
  </div>
  <div class="bg-card border border-line rounded-xl p-5 md:col-span-2">
    <div class="text-sm text-stone-400">وضعیت بدهی</div>
    <div class="mt-1 text-sm">
      <?php if (!$r['allow_debt']): ?>
        بدهی مجاز نیست — موجودی باید مثبت بماند.
      <?php elseif ($r['debt_limit'] === null): ?>
        بدهی نامحدود مجاز است.
      <?php else: ?>
        سقف بدهی مجاز: <span class="text-amber-400"><?= toman((int)$r['debt_limit']) ?></span>
      <?php endif; ?>
    </div>
    <p class="text-xs text-stone-500 mt-2">برای افزایش موجودی با مدیر تماس بگیرید.</p>
  </div>
</div>

<div class="bg-card border border-line rounded-xl overflow-x-auto">
  <h3 class="font-semibold p-4 pb-0">تاریخچه تراکنش‌ها</h3>
  <table class="w-full text-sm mt-2">
    <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="text-right p-2">تاریخ</th><th class="text-right p-2">نوع</th><th class="text-right p-2">مبلغ</th><th class="text-right p-2">مانده</th><th class="text-right p-2">شرح</th></tr></thead>
    <tbody>
    <?php foreach ($txs as $t): ?>
      <tr class="border-t border-line">
        <td class="p-2 text-xs whitespace-nowrap"><?= jdate($t['created_at']) ?></td>
        <td class="p-2"><?= e($typeLabels[$t['type']] ?? $t['type']) ?></td>
        <td class="p-2 <?= (int)$t['amount']<0?'text-rose-400':'text-emerald-400' ?>"><?= toman((int)$t['amount']) ?></td>
        <td class="p-2"><?= toman((int)$t['balance_after']) ?></td>
        <td class="p-2 text-xs text-stone-400"><?= e($t['description']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$txs): ?><tr><td colspan="5" class="p-4 text-center text-stone-500">تراکنشی نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<div class="flex justify-center gap-1 mt-4 text-sm flex-wrap">
  <?php for ($i = max(1,$page-3); $i <= min($pages,$page+3); $i++): ?>
    <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i===$page?'bg-brand':'bg-card2' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
