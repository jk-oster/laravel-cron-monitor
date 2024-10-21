import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'
import { defineUserConfig } from 'vuepress'

export default defineUserConfig({
  bundler: viteBundler(),
  theme: defaultTheme(),

  lang: 'en-US',
  title: 'Cron Monitor for Laravel',
  description: 'Laravel package to monitor external cron jobs. Inspired by spatie uptime monitor',
  base: '/laravel-cron-monitor/',
});
