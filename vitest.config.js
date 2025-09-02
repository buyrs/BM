import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/js/setup.js'],
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
      'ziggy-js': resolve(__dirname, './vendor/tightenco/ziggy/dist/vue.m.js'),
    },
  },
})