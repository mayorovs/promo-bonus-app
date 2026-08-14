import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      // Keeps imports and `@use '@/styles/...'` stable regardless of how
      // deeply a component is nested.
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // Bind to every interface, otherwise the dev server is unreachable from
    // outside the container.
    host: true,
    port: 5173,
    // Fail loudly instead of silently moving to another port.
    strictPort: true,
    watch: {
      // Bind mounts on Windows do not deliver filesystem events into the
      // container, so changes are only noticed by polling.
      usePolling: true,
    },
  },
})
