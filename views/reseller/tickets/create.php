<div class="max-w-xl">
  <h2 class="text-lg font-semibold mb-4">تیکت جدید</h2>
  <form method="post" action="/panel/tickets" class="glass rounded-2xl p-4 space-y-3">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs text-stone-400 mb-1">موضوع</label>
      <input name="subject" value="<?= old('subject') ?>" required class="inp">
    </div>
    <div>
      <label class="block text-xs text-stone-400 mb-1">اولویت</label>
      <select name="priority" class="inp">
        <option value="normal">عادی</option>
        <option value="low">کم</option>
        <option value="high">فوری</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-stone-400 mb-1">متن پیام</label>
      <textarea name="body" rows="5" required class="inp"><?= old('body') ?></textarea>
    </div>
    <div class="flex gap-2">
      <button class="btn-brand px-6 py-2 rounded-lg text-sm">ارسال تیکت</button>
      <a href="/panel/tickets" class="bg-white/10 hover:bg-white/20 px-6 py-2 rounded-lg text-sm">انصراف</a>
    </div>
  </form>
</div>
