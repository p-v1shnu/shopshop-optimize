/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './storage/framework/views/*.php',
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    fontFamily: {
      'sans': ['Noto Sans', 'Noto Sans Lao', 'sans-serif']
    },
    extend: {
      colors: {
        'mineral-blue': '#38B0E6',
        'mineral-gray': '#F9F9F9',
        'primary': '#F04251',
        'secondary': '#6B7280',
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
