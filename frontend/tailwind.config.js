/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
        heading: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
      },
      colors: {
        alpharo: {
          50: '#F0F6FF',
          100: '#E0EEFE',
          200: '#BAE0FD',
          300: '#7DC4FB',
          400: '#38A3F6',
          500: '#0B4DA2', // Primary Enterprise Navy from reference
          600: '#093F88',
          700: '#07326E',
          800: '#052554',
          900: '#041B3E',
          950: '#021028',
        },
        accent: {
          400: '#FBBF24',
          500: '#F5A623', // Warm golden yellow from reference active tab
          600: '#D97706',
          700: '#B45309',
        }
      }
    },
  },
  plugins: [],
}
