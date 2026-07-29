const plugin = require( 'tailwindcss/plugin' )
const { screens } = require( 'tailwindcss/defaultTheme' );
const utils = require( './tailwind.utils.cjs' );

let remSpacings = utils.generateSpacings( 5, 200 );
module.exports = {
  mode: 'jit',
  content: [
    "../*.php",
    "../gutenberg-blocks/**/*.php",
    "../inc/**/*.php",
    "../templates/**/*.php",
    "../woocommerce/**/*.php",
    "../static/**/*.php",
    "./src/modules/**/*.js",
    './tailwind/theme.config.classes.txt',
  ],
  blocklist: [ 'uppercase' ],
  future: { hoverOnlyWhenSupported: true, },
  theme: {
    extend: {
      fontFamily: {
        'main': [ 'Noto Sans', 'sans-serif' ],
        'asty': [ 'CF Asty Pro', 'Noto Sans', 'sans-serif' ],
      },
      fontSize: {
        'base': [ 'clamp(5px, calc(100vw * 10 / 375), 10px)' ],
        'base-md': [ 'calc(100vw * 7.5 / 768 )' ],
        'base-lg': [ 'calc(100vw * 10 / 1440)' ],
        ...utils.get_theme_text_styles()
      },
      fontWeight: {
        bold: '700'
      },
      letterSpacing: { 'none': '0px', 'tight': '-0.5px', 'normal': '0px', 'wide': '0.5px', 'wider': '1px', 'widest': '2px' },
      colors: ( { colors } ) => ( utils.get_theme_colors() ),
      spacing: ( { theme } ) => ( { ...remSpacings, ...utils.get_spacing( 24 ), ...utils.get_spacing( 12 ), ...utils.get_blocks_spacing() } ),
      width: ( { theme } ) => ( { ...remSpacings, ...utils.get_spacing( 24 ), ...utils.get_spacing( 12 ) } ),
      screens: { xxs: '375px', xs: '500px', ...screens, xxl: '2560px', 'md-max': { max: '767px' }, 'lg-max': { max: '1023px' } },
      gridTemplateColumns: { '24': 'repeat(24, minmax(0, 1fr))', },
      zIndex: { 1: '1', 2: '2', 10: '10', 100: '100' },
      animation: {
        pulse: 'pulse 0.7s infinite',
        heart: 'heart 1.2s infinite',
        loading: 'loading 0.5s infinite',
        'loading-low': 'loading-low 0.7s infinite',
        'loading-second': 'loading-low 1s infinite',
        'loading-dots': 'loading-dots 0.7s ease-in-out infinite',
        none: 'none',
        spin: 'spin 1s linear infinite',
        ping: 'ping 1s cubic-bezier(0, 0, 0.2, 1) infinite',
        bounce: 'bounce 1s infinite',
        breathing: 'breathing 3s ease-in-out infinite',
        'search-loading': 'search-loading 1.2s ease-in-out infinite',
      },
      keyframes: {
        breathing: { '0%, 100%': { transform: 'scale(0.6)', opacity: '1' }, '50%': { transform: 'scale(1.1)', opacity: '0.7' } },
        bounce: { '0%, 100%': { transform: 'translateY(-100%)', animationTimingFunction: 'cubic-bezier(0.8,0,1,1)' }, '50%': { transform: 'none', animationTimingFunction: 'cubic-bezier(0,0,0.2,1)' } },
        ping: { '75%, 100%': {transform: 'scale(2)', opacity: '0' } },
        pulse: { '50%': { opacity: '.5' } },
        heart: {
          '0%, 100%': {transform: 'scale(1)'},
          '25%': {transform: 'scale(1.2)'},
          '40%': {transform: 'scale(0.95)'},
          '60%': {transform: 'scale(1.25)'},
        },
       'loading-dots': {
         '0%, 100%': {transform: 'translateX(0)'},
         '50%': {transform: 'translateX(1.4rem)',},
       },
        'search-loading': {
          '0%': {
            transform: 'translate(0rem, -0.18rem) ',
          },

          '12.5%': {
            transform: 'translate(0.13rem, -0.13rem) ',
          },

          '25%': {
            transform: 'translate(0.18rem, 0rem) ',
          },

          '37.5%': {
            transform: 'translate(0.13rem, 0.13rem) ',
          },

          '50%': {
            transform: 'translate(0rem, 0.18rem) ',
          },

          '62.5%': {
            transform: 'translate(-0.13rem, 0.13rem) ',
          },

          '75%': {
            transform: 'translate(-0.18rem, 0rem) ',
          },

          '87.5%': {
            transform: 'translate(-0.13rem, -0.13rem) ',
          },

          '100%': {
            transform: 'translate(0rem, -0.18rem) ',
          },
        },
      },
      borderRadius: {
        default: '0.25rem',
        '15': '1.5rem',
        '20': '2rem',
        '25': '2.5rem',
        '30': '3rem',
        '40': '4rem',
        'full': '9999px',
      },
    },
  },
  plugins: [
    require( "@designbycode/tailwindcss-text-stroke" ),
    require( '@tailwindcss/typography' ),
    require( '@tailwindcss/line-clamp' ),
    plugin( ( { addUtilities, e, theme, variants } ) => {
      addUtilities( { '[class*="flex-gap-"]': { marginLeft: 'calc(-1 * var(--gap))', '& > *': { paddingLeft: 'calc(var(--gap))' } } } );
      Object.entries( remSpacings ).forEach( ( [ key, value ] ) => {
        addUtilities( { [ `.flex-gap-${key}` ]: { '--gap': value } }, variants( 'spacing' ) );
      } );
    } ),
  ],
}
