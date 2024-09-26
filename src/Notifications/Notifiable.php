<?php

namespace JkOster\CronMonitor\Notifications;

use Illuminate\Notifications\Notifiable as NotifiableTrait;

class Notifiable
{
    use NotifiableTrait;

    /**
     * @return string|null
     */
    public function routeNotificationForMail()
    {
        return config('cron-monitor.notifications.mail.to');
    }

    /**
     * @return string|null
     */
    public function routeNotificationForSlack()
    {
        return config('cron-monitor.notifications.slack.webhook_url');
    }

    public function getKey(): string
    {
        return static::class;
    }
}
