<?php $appName = config_value('app_name', 'USVSIR Panel'); ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود — <?= e($appName) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:'Vazirmatn',sans-serif}</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <?php foreach (flash() as $f):
      $c = ['success' => 'bg-emerald-600', 'error' => 'bg-rose-600', 'info' => 'bg-sky-600', 'warning' => 'bg-amber-600'][$f['type']] ?? 'bg-slate-700'; ?>
      <div class="<?= $c ?> text-white px-4 py-2 rounded-lg mb-3 text-sm text-center"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</body>
</html>
