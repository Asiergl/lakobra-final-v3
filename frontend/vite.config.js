import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// ─────────────────────────────────────────────────────────────────
//  Estructura del proyecto en XAMPP (htdocs):
//
//  htdocs/
//  └── lakobra/
//      ├── frontend/   ← aquí corres: npm run dev
//      └── backend/    ← Apache sirve PHP desde aquí
//
//  El proxy redirige las llamadas /api del frontend
//  → http://localhost/lakobra/backend  (Apache + PHP)
//
//  Si tu XAMPP usa otro puerto (ej: 8080) cambia el target:
//      target: 'http://localhost:8080',
// ─────────────────────────────────────────────────────────────────

export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    tailwindcss(),
  ],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },

  server: {
    proxy: {
      // Cualquier llamada a /api/... se redirige al backend PHP
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        // /api/eventos  →  /lakobra/backend/eventos
        rewrite: (path) => path.replace(/^\/api/, '/lakobra-final-v3/backend'),
      }
    }
  }
})
