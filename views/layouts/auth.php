<?php $appName = config_value('app_name', 'Panel'); ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود — <?= e($appName) ?></title>
<link rel="stylesheet" href="/assets/css/tw.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="text-stone-100 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm fade-up">
    <?php foreach (flash() as $f):
      $c = ['success' => 'from-emerald-500/20 border-emerald-500/40 text-emerald-300', 'error' => 'from-rose-500/20 border-rose-500/40 text-rose-300', 'info' => 'from-brand/20 border-brand/40 text-brand-light', 'warning' => 'from-amber-500/20 border-amber-500/40 text-amber-300'][$f['type']] ?? 'from-white/10 border-white/20'; ?>
      <div class="bg-gradient-to-l <?= $c ?> border px-4 py-2.5 rounded-xl mb-3 text-sm text-center"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</body>
</html>
