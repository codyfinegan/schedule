import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Each page is a separate Vite entry (client/src/entries/*.ts), mounted as
// a Vue island into a PHP-rendered layout (see resources/views/). Built
// into ../storage/frontend, outside the web root -- PHP's ViteManifest
// (src/View/ViteManifest.php) resolves entry -> hashed asset URLs from the
// manifest, and FrontendController streams the built /assets/* files,
// since nothing under storage/ is ever served directly by Caddy.
export default defineConfig({
  base: '/',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    outDir: '../storage/frontend',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        login: 'src/entries/login.ts',
        blocks: 'src/entries/blocks.ts',
        calendar: 'src/entries/calendar.ts',
        admin: 'src/entries/admin.ts',
      },
    },
  },
  server: {
    cors: true,
    origin: 'http://127.0.0.1:5173',
    proxy: {
      '/api': 'http://127.0.0.1:8082',
    },
  },
})
