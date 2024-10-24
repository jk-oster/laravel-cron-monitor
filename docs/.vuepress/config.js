import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'
import { defineUserConfig } from 'vuepress'
import { searchPlugin } from '@vuepress/plugin-search'
import { shikiPlugin } from '@vuepress/plugin-shiki'

const isProd = process.env.NODE_ENV === 'production'

export default defineUserConfig({
  bundler: viteBundler(),  
  theme: defaultTheme({
    navbar: [
      { text: 'Author', link: 'https://jakobosterberger.com' },
      { text: 'Blog', link: 'https://jakobosterberger.com/posts' }
    ],

    repo: 'jk-oster/laravel-cron-monitor',
    docsBranch: 'gh-pages',
    docsDir: './docs',
    editLink: true,
    editLinkText: 'Edit this page on GitHub',
    sidebarDepth: 2,
    sidebar: [
      '/README.md',
      '/overview.md',
      '/usage.md',
      '/advanced-usage.md',
    ],
    home: '/',
    colorMode: 'auto',
  }),

  lang: 'en-US',
  title: 'Cron Monitor for Laravel',
  description: 'Laravel package to monitor external cron jobs. Inspired by spatie uptime monitor',
  base: '/laravel-cron-monitor/',

  plugins: [
    searchPlugin(),

    // only enable shiki plugin in production mode
    isProd
      ? shikiPlugin({
          langs: ['bash', 'diff', 'json', 'md', 'ts', 'vue', 'php'],
          theme: 'dark-plus',
        })
      : [],
  ],

});
