/** Tailwind build config — self-hosted (no CDN at runtime). */
module.exports = {
  content: ['./views/**/*.php', './public/index.php', './public/assets/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['Arad', 'sans-serif'] },
      colors: {
        brand: { DEFAULT: '#f97316', light: '#fb923c', dark: '#ea580c' },
        ink: '#0a0a0c', card: '#141210', card2: '#1e1a16', line: '#2a241e', line2: '#3a322a',
      },
    },
  },
  safelist: [
    // colours chosen dynamically in PHP arrays (badges, KPI cards, flash types)
    'bg-emerald-600', 'bg-sky-600', 'bg-violet-600', 'bg-amber-600', 'bg-rose-600', 'bg-cyan-600',
    'bg-emerald-600/20', 'text-emerald-400', 'bg-rose-600/20', 'text-rose-400',
    'bg-amber-600/20', 'text-amber-400', 'bg-sky-600/20',
    'text-emerald-300', 'text-rose-300', 'text-amber-300', 'text-brand-light',
    'bg-amber-600', 'hover:bg-amber-500', 'bg-rose-600', 'hover:bg-rose-500',
  ],
};
