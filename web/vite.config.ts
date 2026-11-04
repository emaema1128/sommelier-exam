import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    // /api へのリクエストは Go のサーバーへ転送する(同じオリジンに見えるので CORS の設定が要らない)
    proxy: {
      '/api': 'http://localhost:8080',
    },
  },
})
