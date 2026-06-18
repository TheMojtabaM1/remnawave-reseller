<?php $strategies = ['NO_RESET'=>'بدون ریست','DAY'=>'روزانه','WEEK'=>'هفتگی','MONTH'=>'ماهانه']; ?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-lg font-semibold">تاریخچه‌ی پلن <?= $plan ? '«'.e($plan['name']).'»' : '(حذف‌شده)' ?></h2>
  <a href="/owner/plans" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm">بازگشت</a>
</div>

<div class="glass rounded-2xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-card2/60 text-stone-300 text-xs"><tr><th class="text-right p-3">تاریخ</th><th class="text-right p-3">رویداد</th><th class="text-right p-3">نام</th><th class="text-right p-3">حجم</th><th class="text-right p-3">مدت</th><th class="text-right p-3">قیمت</th><th class="text-right p-3">بازگردانی</th></tr></thead>
    <tbody>
    <?php foreach ($history as $h): $s = json_decode((string)$h['snapshot'], true) ?: []; ?>
      <tr class="border-t border-line">
        <td class="p-3 text-xs whitespace-nowrap"><?= shamsi($h['created_at']) ?></td>
        <td class="p-3 text-xs"><?= $h['action']==='delete'?'حذف':'ویرایش' ?></td>
        <td class="p-3"><?= e($s['name'] ?? '—') ?></td>
        <td class="p-3"><?= (int)($s['volume_gb'] ?? 0) ?>گیگ</td>
        <td class="p-3"><?= (int)($s['duration_days'] ?? 0) ?>روز</td>
        <td class="p-3"><?= toman((int)($s['price'] ?? 0)) ?></td>
        <td class="p-3">
          <?php if ($plan): ?>
            <form method="post" action="/owner/plans/<?= $planId ?>/restore/<?= $h['id'] ?>" onsubmit="return confirm('بازگردانی پلن به این نسخه؟')">
              <?= csrf_field() ?><button class="bg-brand hover:bg-brand-light px-3 py-1 rounded-lg text-xs">بازگردانی</button>
            </form>
          <?php else: ?><span class="text-stone-600 text-xs">—</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$history): ?><tr><td colspan="7" class="p-6 text-center text-stone-500">تاریخچه‌ای ثبت نشده است.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
