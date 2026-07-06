import vue from '@vitejs/plugin-vue2'
import laravel from 'laravel-vite-plugin'
import legacy from '@vitejs/plugin-legacy'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.scss',
        'resources/js/app.js',
        'resources/js/echo.js',
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    legacy({
      targets: ['defaults', 'not IE 11']
    }),
  ],
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm.js'
    }
  },
  build: {
    outDir: './public/build_prod'
  }
})
