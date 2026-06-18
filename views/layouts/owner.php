<?php
use App\Core\Auth;
use App\Services\AlertService;
$appName = config_value('app_name', 'Panel');
$unread  = AlertService::unreadCount();
$openTickets = \App\Controllers\Owner\TicketController::openCount();
$cur     = $_SERVER['REQUEST_URI'] ?? '';
$nav = [
    '/owner'             => ['داشبورد', 'M3 12l9-9 9 9M5 10v10h14V10'],
    '/owner/resellers'   => ['نمایندگان', 'M17 20v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100 8 4 4 0 000-8z'],
    '/owner/plans'       => ['پلن‌ها', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    '/owner/templates'   => ['قالب‌ها', 'M4 5h16M4 12h10M4 19h7'],
    '/owner/pricing'     => ['قیمت‌گذاری', 'M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6'],
    '/owner/reports'     => ['گزارش‌های مالی', 'M3 3v18h18M7 14l4-4 3 3 5-6'],
    '/owner/leaderboard' => ['برترین‌ها', 'M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM4 5h3M17 5h3'],
    '/owner/bulk'        => ['عملیات گروهی', 'M4 6h16M4 12h16M4 18h16'],
    '/owner/monitor'     => ['کاربران آنلاین', 'M12 12a4 4 0 100-8 4 4 0 000 8zM6 20a6 6 0 0112 0'],
    '/owner/nodes'       => ['سلامت نودها', 'M5 12a7 7 0 0114 0M8.5 12a3.5 3.5 0 017 0M12 12h.01'],
    '/owner/alerts'      => ['هشدارها', 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0'],
    '/owner/tickets'     => ['تیکت‌ها', 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z'],
    '/owner/statements'  => ['صورتحساب‌ها', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z'],
    '/owner/audit'       => ['لاگ فعالیت‌ها', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2'],
    '/owner/backups'     => ['پشتیبان‌گیری', 'M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2'],
    '/owner/settings'    => ['تنظیمات', 'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12a7 7 0 00-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 00-1.7-1L14 2h-4l-.8 3a7 7 0 00-1.7 1l-2.4-1-2 3.4 2 1.6a7 7 0 000 2l-2 1.6 2 3.4 2.4-1a7 7 0 001.7 1L10 22h4l.8-3a7 7 0 001.7-1l2.4 1 2-3.4-2-1.6a7 7 0 00.1-1z'],
];
function icon($d){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 shrink-0"><path d="'.$d.'"/></svg>'; }
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'مدیریت') ?> — <?= e($appName) ?></title>
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Arad-Regular.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Arad-SemiBold.woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('/assets/css/tw.css') ?>">
<link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= asset('/assets/js/app.js') ?>"></script>
</head>
<body class="text-stone-100 min-h-screen">
<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed md:sticky top-0 z-40 -translate-x-full md:translate-x-0 transition-transform duration-300 w-64 h-screen glass border-l border-white/5 overflow-y-auto">
    <div class="p-5 border-b border-white/5 flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-light to-brand-dark logo-dot flex items-center justify-center font-extrabold"><?= e(mb_strtoupper(mb_substr($appName,0,1))) ?></div>
      <div>
        <div class="text-lg font-extrabold text-gradient leading-tight"><?= e($appName) ?></div>
        <div class="text-[10px] text-stone-400 tracking-wide">ADMIN CONSOLE</div>
      </div>
    </div>
    <nav class="p-3 space-y-1">
      <?php foreach ($nav as $href => [$label, $d]):
        $active = ($href === '/owner') ? ($cur === '/owner') : str_starts_with($cur, $href); ?>
        <a href="<?= $href ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition <?= $active ? 'nav-active' : 'text-stone-300 hover:bg-white/5 hover:text-white' ?>">
          <?= icon($d) ?><span class="flex-1"><?= e($label) ?></span>
          <?php if ($href === '/owner/alerts' && $unread > 0): ?><span class="bg-brand text-white text-[11px] rounded-full px-2 py-0.5"><?= $unread ?></span><?php endif; ?>
          <?php if ($href === '/owner/tickets' && $openTickets > 0): ?><span class="bg-brand text-white text-[11px] rounded-full px-2 py-0.5"><?= $openTickets ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div onclick="document.getElementById('sidebar').classList.add('-translate-x-full')" id="ovl" class="md:hidden fixed inset-0 bg-black/50 z-30 hidden"></div>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-20 glass border-b border-white/5 px-4 py-3 flex items-center justify-between">
      <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('ovl').classList.toggle('hidden')" class="md:hidden text-stone-300 text-xl">☰</button>
      <div class="font-bold text-lg"><?= e($title ?? '') ?></div>
      <div class="flex items-center gap-3">
        <a href="/owner/alerts" class="relative text-stone-300 hover:text-brand transition"><?= icon('M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9') ?><?php if ($unread > 0): ?><span class="absolute -top-1.5 -left-1.5 bg-brand text-white text-[10px] rounded-full px-1.5"><?= $unread ?></span><?php endif; ?></a>
        <span class="text-sm text-stone-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400"></span><?= e(Auth::name()) ?></span>
        <form method="post" action="/logout"><?= csrf_field() ?><button class="text-sm text-stone-400 hover:text-brand transition">خروج</button></form>
      </div>
    </header>

    <main class="p-4 md:p-6 flex-1 fade-up">
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
