<div class="grid lg:grid-cols-3 gap-4">
  <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 h-fit">
    <h3 class="font-semibold mb-3">پله قیمتی جدید</h3>
    <p class="text-xs text-slate-500 mb-3">قیمت هر گیگ با افزایش حجم کاهش می‌یابد. هنگام قیمت‌گذاری سفارشی، بالاترین پله‌ای که <code>min_gb ≤ حجم</code> باشد اعمال می‌شود.</p>
    <form method="post" action="/owner/pricing" class="space-y-3">
      <?= csrf_field() ?>
      <select name="scope" id="scope" onchange="document.getElementById('planRow').style.display=this.value==='plan'?'block':'none'" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
        <option value="global">سراسری</option>
        <option value="plan">مخصوص پلن</option>
      </select>
      <div id="planRow" style="display:none">
        <select name="plan_id" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
          <?php foreach ($plans as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <input type="number" name="min_gb" placeholder="حداقل حجم (گیگ)" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      <input type="number" name="price_per_gb" placeholder="قیمت هر گیگ (تومان)" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm">
      <button class="w-full bg-emerald-600 hover:bg-emerald-500 py-2 rounded-lg text-sm">افزودن</button>
    </form>
  </div>

  <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-4">
    <h3 class="font-semibold mb-3">پله‌های قیمتی</h3>
    <table class="w-full text-sm">
      <thead class="text-slate-400 text-xs"><tr><th class="text-right p-2">دامنه</th><th class="text-right p-2">از حجم</th><th class="text-right p-2">قیمت هر گیگ</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($tiers as $t): ?>
        <tr class="border-t border-slate-800">
          <td class="p-2"><?= $t['scope']==='global' ? 'سراسری' : 'پلن: '.e($t['plan_name']) ?></td>
          <td class="p-2"><?= (int)$t['min_gb'] ?> گیگ به بالا</td>
          <td class="p-2"><?= toman((int)$t['price_per_gb']) ?></td>
          <td class="p-2"><form method="post" action="/owner/pricing/<?= $t['id'] ?>/delete"><?= csrf_field() ?><button class="text-rose-400 text-xs">حذف</button></form></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$tiers): ?><tr><td colspan="4" class="p-4 text-center text-slate-500">پله‌ای تعریف نشده است.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
