<?php

// config for JkOster/CronMonitor
return [
    'base_url' => env('APP_URL'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'api' => [
        'enabled' => true,
        'prefix' => 'cron-monitor',
        'middleware' => [],
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail'
     * and 'slack'. Of course you can also specify your own notification classes.
     */
    'notifications' => [

        'notifications' => [
            \JkOster\CronMonitor\Notifications\Notifications\CronCheckFailed::class => ['slack', 'mail'],
            \JkOster\CronMonitor\Notifications\Notifications\CronCheckRecovered::class => ['slack', 'mail'],
        ],

        /*
         * To keep reminding you that a site is down, notifications
         * will be resent every given number of minutes.
         */
        'resend_cron_check_failed_notification_every_minutes' => 60,

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
     * `Spatie\UptimeMonitor\Models\Monitor`.
     */
    'monitor_model' => \JkOster\CronMonitor\Models\Monitor::class,
];
