<?php $appName = config_value('app_name', 'USVSIR Panel'); ?>
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
  <div class="text-center mb-6">
    <div class="text-2xl font-bold text-sky-400"><?= e($appName) ?></div>
    <div class="text-sm text-slate-400 mt-1">ورود به پنل</div>
  </div>
  <form method="post" action="/login" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm text-slate-300 mb-1">نام کاربری</label>
      <input name="username" value="<?= old('username') ?>" autofocus required
             class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 focus:border-sky-500 outline-none">
    </div>
    <div>
      <label class="block text-sm text-slate-300 mb-1">رمز عبور</label>
      <input type="password" name="password" required
             class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 focus:border-sky-500 outline-none">
    </div>
    <button class="w-full bg-sky-600 hover:bg-sky-500 text-white font-semibold rounded-lg py-2 transition">ورود</button>
  </form>
</div>
<p class="text-center text-xs text-slate-600 mt-4">USVSIR — Remnawave Reseller Panel</p>
