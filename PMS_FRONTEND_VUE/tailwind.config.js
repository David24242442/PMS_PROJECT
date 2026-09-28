/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', '"Helvetica Neue"', 'Arial', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
        heading: ['Inter', '"Plus Jakarta Sans"', 'sans-serif'],
      },
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
