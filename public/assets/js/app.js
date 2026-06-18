/* Shared front-end helpers */

/* Copy-to-clipboard with HTTP fallback (navigator.clipboard needs HTTPS). */
function copyText(text, btn) {
  const flash = () => {
    if (!btn) return;
    const old = btn.dataset.label || btn.textContent;
    btn.dataset.label = old;
    btn.textContent = '✓ کپی شد';
    btn.classList.add('!bg-emerald-600');
    setTimeout(() => { btn.textContent = old; btn.classList.remove('!bg-emerald-600'); }, 1500);
  };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(flash).catch(() => fallbackCopy(text, flash));
  } else {
    fallbackCopy(text, flash);
  }
}
function fallbackCopy(text, cb) {
  const t = document.createElement('textarea');
  t.value = text;
  t.setAttribute('readonly', '');
  t.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
  document.body.appendChild(t);
  t.focus(); t.select(); t.setSelectionRange(0, text.length);
  try { document.execCommand('copy'); cb && cb(); } catch (e) {}
  document.body.removeChild(t);
}

/* Bulk selection: live count + select-all sync. */
document.addEventListener('change', (e) => {
  if (e.target.classList && e.target.classList.contains('cb')) updateSelCount();
});
function selectAll(master) {
  document.querySelectorAll('.cb').forEach(c => { c.checked = master.checked; });
  updateSelCount();
}
function updateSelCount() {
  const n = document.querySelectorAll('.cb:checked').length;
  document.querySelectorAll('[data-selcount]').forEach(el => {
    el.textContent = n;
    el.closest('[data-selbar]')?.classList.toggle('opacity-50', n === 0);
  });
}
document.addEventListener('DOMContentLoaded', updateSelCount);

/* Apply Arad + theme colors to all Chart.js charts (fixes garbled labels). */
function applyChartTheme() {
  if (!window.Chart) return;
  Chart.defaults.font.family = 'Arad, sans-serif';
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#a8a29e';
  Chart.defaults.borderColor = 'rgba(255,255,255,.06)';
  Chart.defaults.plugins.legend.labels.color = '#d6d3d1';
  Chart.defaults.plugins.tooltip.titleFont = { family: 'Arad' };
  Chart.defaults.plugins.tooltip.bodyFont = { family: 'Arad' };
}
applyChartTheme();
/* Re-apply once the webfont is ready so canvas text isn't tofu on first paint. */
if (document.fonts && document.fonts.ready) {
  document.fonts.ready.then(() => {
    applyChartTheme();
    if (window.Chart) Object.values(Chart.instances || {}).forEach(c => c.update());
  });
}
