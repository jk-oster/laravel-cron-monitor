import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'
import { defineUserConfig } from 'vuepress'

export default defineUserConfig({
  bundler: viteBundler(),  
  theme: defaultTheme({
    navbar: [
      { text: 'Installation', link: '/#installation-setup' },
      { text: 'Highlevel Overview', link: '/overview.html' },
      { text: 'General Usage', link: '/usage.html' },
      { text: 'Advanced Usage', link: '/advanced-usage.html' },
      { text: 'Author', link: 'https://jakobosterberger.com' },
      { text: 'Blog', link: 'https://jakobosterberger.com/posts' }
    ],

    repo: 'jk-oster/laravel-cron-monitor',
    docsBranch: 'gh-pages',
    docsDir: './docs',
    editLink: true,
    sidebarDepth: 2,
    sidebar: 'heading',
    home: '/',
    colorMode: 'auto',
  }),

  lang: 'en-US',
  title: 'Cron Monitor for Laravel',
  description: 'Laravel package to monitor external cron jobs. Inspired by spatie uptime monitor',
  base: '/laravel-cron-monitor/',

});
