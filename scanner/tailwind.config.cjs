/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [ './index.php', './assets/js/**/*.js' ],
  safelist: [
    'scanner-ribbon--info',
    'scanner-ribbon--warning',
    'scanner-ribbon--urgent',
    'scanner-notification-marker--info',
    'scanner-notification-marker--warning',
    'scanner-notification-marker--urgent',
    'scanner-notification-pill--info',
    'scanner-notification-pill--warning',
    'scanner-notification-pill--urgent',
  ],
  theme: {
    extend: {
      colors: {
        'scanner-bg': '#173276',
        'app-top': '#173276',
        coral: 'rgb(var(--scanner-brand-rgb) / <alpha-value>)',
        'coral-dark': 'rgb(var(--scanner-brand-rgb) / <alpha-value>)',
        'coral-soft': 'rgb(var(--scanner-brand-soft-rgb) / <alpha-value>)',
        ink: '#173276',
        muted: '#8185BE',
        paper: '#FFFFFF',
        cloud: '#F7F5EE',
        line: '#D9D9D9',
        success: '#00741D',
        warning: '#EAC63F',
        error: '#C41313',
      },
      fontFamily: {
        sans: [ 'tt-commons-pro', 'sans-serif' ],
      },
    },
  },
  plugins: [],
};
