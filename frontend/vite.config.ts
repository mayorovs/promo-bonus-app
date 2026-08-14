import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
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
