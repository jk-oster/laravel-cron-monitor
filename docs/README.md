---
title: "Docs"
description: "Laravel package to monitor external cron jobs similat to spatie uptime monitor"
home: false
sidebar: "heading"
footer: "Made with ❤️ by <a href='https://jakobosterberger.com'>Jakob Osterberger</a> (c) 2024"
footerHtml: true
actions:
  - text: Get Started
    link: "/#installation-setup"
    type: primary
  - text: Usage Guide
    link: "/usage.html"
    type: secondary
features:
  - title: Simplicity
    details: Easy to use fluent API for creating trends and reports with sensible defaults.
  - title: Flexibility
    details: Can be used from simple trends up to complex reporting analysis through using Closures.
  - title: Compatible
    details: Package is to the biggest part compatible with the Laravel-Trend package.
next:
  text: Usage Guide
  link: /usage.html
---

::: warning
🏗️ This Page Under Construction and not ready for use yet!
:::

## Introduction

Laravel-cron-monitor is a laravel package that provides a powerful, easy to configure cron job monitors.
It will notify you when your cron job does not execute on time or takes too long (and when it comes back up).
Under the hood, the package leverages Laravel native notifications, so it's easy to use Slack, Telegram or your preferred notification provider.

## How does it work?

When creating a cron monitor a unique identifier (UUID) for your cron job is creted.
All your cron job needs to do is to send a ping with this identifier to the application when the cron job has successfully finished.
Moreover, you can not only monitor if your cron jobs are running punctually and successfully but you can also check that their execution time does not exceed a defined limit.

For more details read the [overview](./overview.md) page.

## Requirements

This cron monitor package requires **PHP 8** or higher and **Laravel 8** or higher. The ``php-intl`` must be installed.

## Installation & Setup

This package is meant to be installed into an existing Laravel application.
If you're not familiar with Laravel head over to the [official documentation](https://laravel.com/docs/) to learn how to set up and use this amazing framework.

Standing in the directory of an existing Laravel application you can install the package via composer:

```bash
composer require spatie/laravel-cron-monitor
```

The package will automatically register itself.

To publish the config file to ``config/cron-monitor.php`` run:

```bash
php artisan vendor:publish --provider="JkOster\CronMonitor\CronMonitorServiceProvider"
```

The default contents of the configuration looks like this:

```php
<?php

// config for JkOster/CronMonitor
return [
    /**
     * Base URL of your application
     */
    'base_url' => env('APP_URL'),

    /**
     * Timezone of your application
     */
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /**
     * API configuration for incoming pings from cron jobs
     */
    'api' => [
        /**
         * Enables default package API route for receiving pings
         */
        'enabled' => true,

        /**
         * Url prefix for the API endpoint
         */
        'prefix' => 'cron-monitor',

        /**
         * Configure any middlewares that the API should use
         */
        'middleware' => [],
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail'
     * and 'slack'. Of course you can also specify your own notification classes.
     */
    'notifications' => [

        /**
         * Enables sending of notifications through the default notifiable
         */
        'enabled' => true,

        'notifications' => [
            \JkOster\CronMonitor\Notifications\Notifications\CronCheckFailed::class => ['slack', 'mail'],
            \JkOster\CronMonitor\Notifications\Notifications\CronCheckRecovered::class => ['slack', 'mail'],
        ],

        'mail' => [
            'to' => ['your@email.com'],
        ],

        'slack' => [
            'webhook_url' => env('CRON_MONITOR_SLACK_WEBHOOK_URL', ''),
        ],

        /*
         * Here you can specify the notifiable to which the notifications should be sent. The default
         * notifiable will use the variables specified in this config file.
         */
        'notifiable' => \JkOster\CronMonitor\Notifications\Notifiable::class,

        /*
         * The date format used in notifications.
         */
        'date_format' => 'd/m/Y',
    ],

    /*
     * To add or modify behaviour to the Monitor model you can specify your
     * own model here. The only requirement is that it should extend
     * `\JkOster\CronMonitor\Models\CronMonitor`.
     */
    'monitor_model' => \JkOster\CronMonitor\Models\CronMonitor::class,
];

```

As a last step, run the migrations to create the monitors table.

```bash
php artisan migrate
```

### Scheduling

After you have performed the basic installation you can check the health state of your cron jobs using the ``cron-monitor:process-checks`` command.

You can schedule the commands, like any other command, in the console Kernel.

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('cron-monitor:process-checks')->everyMinute();
}
```
