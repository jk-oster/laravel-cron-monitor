<?php

namespace JkOster\CronMonitor\Notifications;

use Illuminate\Notifications\Notification;
use JkOster\CronMonitor\Models\Monitor;

abstract class BaseNotification extends Notification
{
    public function __construct(protected $event)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via(object $notifiable)
    {
        return config('cron-monitor.notifications.notifications.' . static::class);
    }

    public function getLocationDescription(): string
    {
        $configuredLocation = config('cron-monitor.notifications.location');

        if ($configuredLocation == '') {
            return '';
        }

        return "Monitor {$configuredLocation}";
    }

    public function isStillRelevant(): bool
    {
        return true;
    }
}
