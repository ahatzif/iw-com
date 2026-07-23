const plugin = require('tailwindcss/plugin')

module.exports = {
  content: ["../*.html",  "../**/*.php",  "./src/modules/**/*.js" ],
  mode: 'jit',
  theme: {
    container: {},
    extend: {
      colors: ({ colors }) => ({
        'white': 'white',
        'black' : '#000',
        'gray-light' : '#75787B',
        'gray-soft' : '#bfbfbf',
        'blue': '#00aeef',
        'red': '#fd0307'
      }),
    },
  },
}
