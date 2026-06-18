<?php use App\Core\Csrf; ?>
<div id="aiWrap">
  <button id="aiToggle" onclick="aiOpen()" title="دستیار هوشمند"
    class="fixed bottom-5 left-5 z-50 w-14 h-14 rounded-full btn-brand flex items-center justify-center text-2xl shadow-2xl">🤖</button>

  <div id="aiBox" class="fixed bottom-5 left-5 z-50 w-[92vw] max-w-sm h-[70vh] max-h-[560px] glass rounded-2xl flex-col hidden">
    <div class="flex items-center justify-between p-3 border-b border-white/10">
      <div class="flex items-center gap-2 font-bold"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span> دستیار هوشمند</div>
      <button onclick="aiClose()" class="text-stone-400 hover:text-brand text-xl leading-none">×</button>
    </div>
    <div id="aiMsgs" class="flex-1 overflow-y-auto p-3 space-y-2 text-sm">
      <div class="bg-white/5 rounded-xl p-2.5">سلام! 👋 برای راهنمای اتصال، رفع اشکال کانفیگ یا کار با پنل، سوالت رو بپرس.</div>
    </div>
    <form id="aiForm" class="p-3 border-t border-white/10 flex gap-2">
      <input id="aiInput" autocomplete="off" placeholder="سوالت رو بنویس…" class="flex-1 bg-card2 border border-line2 rounded-xl px-3 py-2 text-sm">
      <button class="btn-brand px-4 rounded-xl text-sm">ارسال</button>
    </form>
  </div>
</div>
<script>
const AI_CSRF = <?= json_encode(Csrf::token()) ?>;
function aiOpen(){ document.getElementById('aiBox').classList.remove('hidden'); document.getElementById('aiBox').classList.add('flex'); document.getElementById('aiToggle').classList.add('hidden'); document.getElementById('aiInput').focus(); }
function aiClose(){ document.getElementById('aiBox').classList.add('hidden'); document.getElementById('aiBox').classList.remove('flex'); document.getElementById('aiToggle').classList.remove('hidden'); }
function aiBubble(text, mine){
  const d = document.createElement('div');
  d.className = mine ? 'bg-brand/20 border border-brand/30 rounded-xl p-2.5 ml-8 whitespace-pre-wrap' : 'bg-white/5 rounded-xl p-2.5 mr-8 whitespace-pre-wrap';
  d.textContent = text;
  const box = document.getElementById('aiMsgs'); box.appendChild(d); box.scrollTop = box.scrollHeight;
  return d;
}
document.getElementById('aiForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const inp = document.getElementById('aiInput'); const msg = inp.value.trim(); if(!msg) return;
  inp.value=''; aiBubble(msg, true);
  const loading = aiBubble('در حال نوشتن…', false);
  try{
    const fd = new URLSearchParams(); fd.append('_csrf', AI_CSRF); fd.append('message', msg);
    const r = await fetch('/assistant', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:fd});
    const j = await r.json(); loading.textContent = j.reply || 'پاسخی دریافت نشد.';
  }catch(err){ loading.textContent = 'خطا در ارتباط.'; }
});
</script>
