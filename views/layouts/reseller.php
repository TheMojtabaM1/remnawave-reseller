<?php
use App\Core\Auth;
$appName = config_value('app_name', 'USVSIR Panel');
$cur = $_SERVER['REQUEST_URI'] ?? '';
$nav = [
    '/panel'         => ['داشبورد', '🏠'],
    '/panel/configs' => ['کانفیگ‌ها', '🔑'],
    '/panel/configs/create' => ['ساخت کانفیگ', '➕'],
    '/panel/wallet'  => ['کیف پول', '👛'],
];
$rsl = Auth::reseller();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'پنل نماینده') ?> — <?= e($appName) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{fontFamily:{sans:['Vazirmatn','sans-serif']}}}</script>
<style>body{font-family:'Vazirmatn',sans-serif}</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<div class="flex min-h-screen">
  <aside id="sidebar" class="fixed md:static z-40 -translate-x-full md:translate-x-0 transition-transform w-60 bg-slate-900 border-l border-slate-800 h-screen md:h-auto">
    <div class="p-4 border-b border-slate-800">
      <div class="text-lg font-bold text-emerald-400"><?= e($appName) ?></div>
      <div class="text-xs text-slate-400 mt-1">پنل نمایندگی</div>
    </div>
    <nav class="p-2 space-y-1">
      <?php foreach ($nav as $href => [$label, $icon]):
        $active = ($href === '/panel') ? ($cur === '/panel') : ($cur === $href || ($href !== '/panel/configs/create' && str_starts_with($cur, $href))); ?>
        <a href="<?= $href ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm <?= $active ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
          <span><?= $icon ?></span><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="bg-slate-900 border-b border-slate-800 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
      <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="md:hidden text-slate-300">☰</button>
      <div class="font-semibold"><?= e($title ?? '') ?></div>
      <div class="flex items-center gap-3">
        <span class="text-sm <?= ($rsl && (int)$rsl['balance'] < 0) ? 'text-rose-400' : 'text-emerald-400' ?>">👛 <?= toman($rsl ? (int)$rsl['balance'] : 0) ?></span>
        <span class="text-sm text-slate-400">🧑‍💼 <?= e(Auth::name()) ?></span>
        <form method="post" action="/logout"><?= csrf_field() ?><button class="text-sm text-rose-400 hover:text-rose-300">خروج</button></form>
      </div>
    </header>
    <main class="p-4 md:p-6 flex-1">
      <?php foreach (flash() as $f):
        $c = ['success' => 'bg-emerald-600', 'error' => 'bg-rose-600', 'info' => 'bg-sky-600', 'warning' => 'bg-amber-600'][$f['type']] ?? 'bg-slate-700'; ?>
        <div class="<?= $c ?> text-white px-4 py-2 rounded-lg mb-3 text-sm"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
