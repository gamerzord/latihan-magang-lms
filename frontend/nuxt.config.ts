// https://nuxt.com/docs/api/configuration/nuxt-config
import vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  typescript: { shim: false },
  pages: true,

  css: [
    'vuetify/styles',
    '@mdi/font/css/materialdesignicons.css',
    'assets/styles/index.scss'
  ],

  runtimeConfig: {
    public: {
      apiBase: 'https://lms.test/api',
    },
  },

  vite: {
    ssr: {
      noExternal: ['vuetify'],
    },
    vue: {
      template: {
        transformAssetUrls,
      },
    },
  },

  modules: [
    '@nuxt/eslint', '@nuxt/icon',
    async (_options, nuxt) => {
      nuxt.hooks.hook('vite:extendConfig', (config) => {
        // @ts-expect-error
        config.plugins.push(
          vuetify({
            autoImport: true,
          })
        )
      })
    },
  ],

  build: {
    transpile: ['vuetify'],
  },
})
