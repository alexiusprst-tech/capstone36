import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [laravel({
    input: ['src/main.js'],
    publicDirectory: '../backend/public',
    hotFile: '../backend/public/hot',
    refresh: ['../backend/resources/views/**', '../backend/routes/**'],
  }), vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }), tailwindcss()],
  publicDir: false,
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  build: {
    outDir: path.resolve(__dirname, '../backend/public/build'),
    emptyOutDir: true,
  },
  server: {
    port: 5173,
    host: true,
  },
});
