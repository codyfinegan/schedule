import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Pure SPA build -- output is pushed as-is to the S3 bucket in prod (see
// readme.md), so outDir stays at the default ./dist with no PHP-aware
// manifest handling.
export default defineConfig({
  base: '/',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    proxy: {
      '/api': 'http://127.0.0.1:8086',
    },
  },
})
