const plugin = require('tailwindcss/plugin')
const { screens } = require('tailwindcss/defaultTheme');
const utils = require('./tailwind.utils.cjs');

let remSpacings = utils.generateSpacings(5, 200);
module.exports = {
  mode: 'jit',
  content: [ '../../**/*.php', './src/modules/**/*.js', './tailwind/theme.config.classes.txt' ],
  future: { hoverOnlyWhenSupported: true, },
  theme: {
    extend: {
      fontFamily: { 'main': [ 'Geologica', 'sans-serif' ] },
      fontSize: {
        'base':    [ 'max(7px, calc(100vw * 10 / 767))' ],
        'base-md': [ 'calc(100vw * 10 / 768 )' ],
        'base-lg': [ 'calc(100vw * 10 / 1440 )' ],
        ...utils.get_theme_text_styles()
      },
      letterSpacing: { 'none': '0px', 'tight': '-0.5px', 'normal': '0px', 'wide': '0.5px', 'wider': '1px', 'widest': '2px' },
      colors: ({ colors }) => (utils.get_theme_colors()),
      spacing: ({ theme }) => ({ ...remSpacings, ...utils.get_spacing(24), ...utils.get_spacing(12, '2*var(--mobile-page-padding)'), ...utils.get_blocks_spacing() }),
      width: ({ theme }) => ({ ...remSpacings, ...utils.get_spacing(24), ...utils.get_spacing(12, '2*var(--mobile-page-padding)') }),
      screens: { xxs: '375px', xs: '500px', ...screens, 'md-max': { max: '767px' }, 'lg-max': { max: '1023px' } },
      zIndex: { 1: '1', 2: '2', 10 : '10', 100 : '100' },
      animation: {
        pulse: 'pulse-two 0.7s infinite',
        loading: 'loading 0.5s infinite',
        'loading-low': 'loading-low 0.7s infinite',
        none: 'none',
        spin: 'spin 1s linear infinite',
        ping: 'ping 1s cubic-bezier(0, 0, 0.2, 1) infinite',
        bounce: 'bounce 1s infinite',
        breathing: 'breathing 3s ease-in-out infinite',
      },
      keyframes: {
        breathing: { '0%, 100%': { transform: 'scale(0.6)', opacity: '1' }, '50%': { transform: 'scale(1.1)', opacity: '0.7' } },
      },
    },
  },
  plugins: [ require('@designbycode/tailwindcss-text-stroke'), require('@tailwindcss/typography') ]
}
