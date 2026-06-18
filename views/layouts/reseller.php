<?php
use App\Core\Auth;
$appName = config_value('app_name', 'Panel');
$cur = $_SERVER['REQUEST_URI'] ?? '';
$nav = [
    '/panel'         => ['داشبورد', 'M3 12l9-9 9 9M5 10v10h14V10'],
    '/panel/configs' => ['کانفیگ‌ها', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.7 5.7l-2.5 2.5H9v2H7v2H3v-4l6.3-6.3A6 6 0 1121 9z'],
    '/panel/configs/create' => ['ساخت کانفیگ', 'M12 5v14M5 12h14'],
    '/panel/reports' => ['گزارش‌ها', 'M3 3v18h18M7 14l4-4 3 3 5-6'],
    '/panel/tickets' => ['تیکت‌ها', 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z'],
    '/panel/wallet'  => ['کیف پول', 'M2 7h20v12a2 2 0 01-2 2H4a2 2 0 01-2-2V7zm0 0l3-4h12l3 4M16 13h2'],
];
function icon($d){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 shrink-0"><path d="'.$d.'"/></svg>'; }
$rsl = Auth::reseller();
$bal = $rsl ? (int)$rsl['balance'] : 0;
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'پنل نماینده') ?> — <?= e($appName) ?></title>
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Arad-Regular.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Arad-SemiBold.woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('/assets/css/tw.css') ?>">
<link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= asset('/assets/js/app.js') ?>"></script>
</head>
<body class="text-stone-100 min-h-screen">
<div class="flex min-h-screen">
  <aside id="sidebar" class="fixed md:sticky top-0 right-0 z-40 translate-x-full md:translate-x-0 transition-transform duration-300 w-60 h-screen glass border-l border-white/5 overflow-y-auto">
    <div class="p-5 border-b border-white/5 flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-light to-brand-dark logo-dot flex items-center justify-center font-extrabold"><?= e(mb_strtoupper(mb_substr($appName,0,1))) ?></div>
      <div>
        <div class="text-lg font-extrabold text-gradient leading-tight"><?= e($appName) ?></div>
        <div class="text-[10px] text-stone-400 tracking-wide">RESELLER</div>
      </div>
    </div>
    <nav class="p-3 space-y-1">
      <?php foreach ($nav as $href => [$label, $d]):
        $active = ($href === '/panel') ? ($cur === '/panel') : ($cur === $href || ($href !== '/panel/configs/create' && str_starts_with($cur, $href))); ?>
        <a href="<?= $href ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition <?= $active ? 'nav-active' : 'text-stone-300 hover:bg-white/5 hover:text-white' ?>">
          <?= icon($d) ?><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div onclick="document.getElementById('sidebar').classList.add('translate-x-full')" id="ovl" class="md:hidden fixed inset-0 bg-black/50 z-30 hidden"></div>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-20 glass border-b border-white/5 px-4 py-3 flex items-center justify-between">
      <button onclick="document.getElementById('sidebar').classList.toggle('translate-x-full');document.getElementById('ovl').classList.toggle('hidden')" class="md:hidden text-stone-300 text-xl">☰</button>
      <div class="font-bold text-lg"><?= e($title ?? '') ?></div>
      <div class="flex items-center gap-3">
        <span class="text-sm px-3 py-1 rounded-full <?= $bal < 0 ? 'bg-rose-500/15 text-rose-300' : 'bg-emerald-500/15 text-emerald-300' ?>"><?= toman($bal) ?></span>
        <span class="text-sm text-stone-300 hidden sm:inline"><?= e(Auth::name()) ?></span>
        <form method="post" action="/logout"><?= csrf_field() ?><button class="text-sm text-stone-400 hover:text-brand transition">خروج</button></form>
      </div>
    </header>
    <main class="p-4 md:p-6 flex-1 fade-up">
      <?php $bc = (string) config_value('broadcast_message', ''); if (config_value('broadcast_enabled', '') === '1' && trim($bc) !== ''): ?>
        <div class="bg-gradient-to-l from-brand/25 to-amber-500/15 border border-brand/40 px-4 py-3 rounded-xl mb-3 text-sm flex items-start gap-2">
          <span>📢</span><span class="text-stone-100"><?= e($bc) ?></span>
        </div>
      <?php endif; ?>
      <?php foreach (flash() as $f):
        $c = ['success' => 'from-emerald-500/20 border-emerald-500/40 text-emerald-300', 'error' => 'from-rose-500/20 border-rose-500/40 text-rose-300', 'info' => 'from-brand/20 border-brand/40 text-brand-light', 'warning' => 'from-amber-500/20 border-amber-500/40 text-amber-300'][$f['type']] ?? 'from-white/10 border-white/20'; ?>
        <div class="bg-gradient-to-l <?= $c ?> border px-4 py-2.5 rounded-xl mb-3 text-sm"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
