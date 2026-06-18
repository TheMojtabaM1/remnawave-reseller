<?php $appName = config_value('app_name', 'Panel'); ?>
<div class="glass rounded-3xl p-7 shadow-2xl ring-brand">
  <div class="text-center mb-7">
    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-brand-light to-brand-dark logo-dot flex items-center justify-center text-2xl font-extrabold mb-3"><?= e(mb_strtoupper(mb_substr($appName,0,1))) ?></div>
    <div class="text-2xl font-extrabold text-gradient"><?= e($appName) ?></div>
    <div class="text-xs text-stone-400 mt-1">به پنل خود وارد شوید</div>
  </div>
  <form method="post" action="/login" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs text-stone-400 mb-1.5">نام کاربری</label>
      <input name="username" value="<?= old('username') ?>" autofocus required
             class="w-full bg-card2 border border-line2 rounded-xl px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/30 transition">
    </div>
    <div>
      <label class="block text-xs text-stone-400 mb-1.5">رمز عبور</label>
      <input type="password" name="password" required
             class="w-full bg-card2 border border-line2 rounded-xl px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/30 transition">
    </div>
    <button class="btn-brand w-full rounded-xl py-2.5 mt-2">ورود</button>
  </form>
</div>
