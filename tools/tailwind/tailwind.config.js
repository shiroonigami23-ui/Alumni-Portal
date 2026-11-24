/**
 * Pre-built Tailwind for production (replaces the in-browser Play CDN).
 * Rebuild after adding new classes:  npm --prefix tools/tailwind run build
 */
module.exports = {
  content: [
    '../../index.php', '../../pages/**/*.php', '../../admin/**/*.php', '../../includes/**/*.{php,js}',
    '../../assets/js/**/*.js', '../../offline.html',
  ],
  safelist: [
    { pattern: /^text-(blue|green|amber|red|indigo|purple|emerald|sky|pink|violet)-(400|500|600|700)$/ },
    { pattern: /^bg-(blue|green|amber|red|indigo|purple|emerald)-(50|100|500|600)$/ },
  ],
  theme: { extend: {} },
  plugins: [],
};
