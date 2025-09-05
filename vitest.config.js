import { defineConfig } from 'vitest/config'
import { resolve } from 'path'

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/js/setup.js'],
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
      'ziggy-js': resolve(__dirname, './vendor/tightenco/ziggy/dist/index.js'),
    },
  },
})