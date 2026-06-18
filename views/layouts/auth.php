<?php $appName = config_value('app_name', 'Panel'); ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود — <?= e($appName) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{
  fontFamily:{sans:['Arad','sans-serif']},
  colors:{brand:{DEFAULT:'#f97316',light:'#fb923c',dark:'#ea580c'},ink:'#0a0a0c',card:'#141210',card2:'#1e1a16',line:'#2a241e',line2:'#3a322a'}
}}}</script>
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
