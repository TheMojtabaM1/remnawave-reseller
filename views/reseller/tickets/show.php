<?php
$st = ['open'=>['باز','bg-brand/20 text-brand-light'],'answered'=>['پاسخ داده‌شده','bg-emerald-600/20 text-emerald-400'],'closed'=>['بسته','bg-white/10 text-stone-300']];
[$slabel,$sclass] = $st[$ticket['status']] ?? ['—',''];
?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <div>
    <h2 class="text-lg font-semibold">#<?= $ticket['id'] ?> — <?= e($ticket['subject']) ?></h2>
    <span class="<?= $sclass ?> px-2 py-0.5 rounded text-xs"><?= $slabel ?></span>
  </div>
  <a href="/panel/tickets" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm">بازگشت</a>
</div>

<div class="glass rounded-2xl p-4 space-y-3 mb-4">
  <?php foreach ($messages as $m): $mine = $m['sender_type'] === 'reseller'; ?>
    <div class="flex <?= $mine ? 'justify-start' : 'justify-end' ?>">
      <div class="max-w-[80%] rounded-2xl p-3 text-sm whitespace-pre-wrap <?= $mine ? 'bg-brand/15 border border-brand/30' : 'bg-white/5 border border-white/10' ?>">
        <div class="text-[11px] text-stone-400 mb-1"><?= $mine ? 'شما' : 'پشتیبانی' ?> · <?= shamsi($m['created_at']) ?></div>
        <?= e($m['body']) ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($ticket['status'] !== 'closed'): ?>
<form method="post" action="/panel/tickets/<?= $ticket['id'] ?>/reply" class="glass rounded-2xl p-4 flex flex-col gap-2">
  <?= csrf_field() ?>
  <textarea name="body" rows="3" required placeholder="پاسخ خود را بنویسید…" class="inp"></textarea>
  <button class="btn-brand px-6 py-2 rounded-lg text-sm w-fit">ارسال پاسخ</button>
</form>
<?php else: ?>
<div class="glass rounded-2xl p-4 text-sm text-stone-400 text-center">این تیکت بسته شده است.</div>
<?php endif; ?>
