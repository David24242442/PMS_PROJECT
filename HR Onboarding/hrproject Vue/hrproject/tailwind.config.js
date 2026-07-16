/** @type {import('tailwindcss').Config} */
export default {
  purge: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  content: [],
  theme: {
    fontFamily: {
      sans: ['Inter', 'system-ui', 'sans-serif'],
      heading: ['"Plus Jakarta Sans"', 'sans-serif'],
      serif: ['Georgia', 'serif'],
      mono: ['Menlo', 'monospace'],
    },
    extend: {
      colors: {
        mred: {
          "50": "#fdeeec",
          "100": "#fbddda",
          "200": "#f6bbb5",
          "300": "#f29a8f",
          "400": "#ed786a",
          "500": "#e95645",
          "600": "#ba4537",
          "700": "#8c3429",
          "800": "#5d221c",
          "900": "#2f110e"
        },
      }
    },
  },
  plugins: [],
}
