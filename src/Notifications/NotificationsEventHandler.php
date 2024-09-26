<?php

namespace JkOster\CronMonitor\Notifications;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use JkOster\CronMonitor\Events\CronCheckFailedEvent;
use JkOster\CronMonitor\Events\CronCheckRecoveredEvent;

class NotificationsEventHandler
{
    /** @var \Illuminate\Config\Repository */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen($this->allEventClasses(), function ($event) {
            $notification = $this->determineNotification($event);

            if (! $notification) {
                return;
            }

            if ($notification->isStillRelevant()) {
                $notifiable = $this->determineNotifiable();

                $notifiable->notify($notification);
            }
        });
    }

    protected function determineNotifiable()
    {
        $notifiableClass = $this->config->get('cron-monitor.notifications.notifiable');

        return app($notifiableClass);
    }

    protected function determineNotification($event)
    {
        $eventName = class_basename($event);

        $notificationClass = collect($this->config->get('cron-monitor.notifications.notifications'))
            ->filter(function (array $notificationChannels) {
                return count($notificationChannels);
            })
            ->keys()
            ->first(function ($notificationClass) use ($eventName) {
                $notificationName = class_basename($notificationClass);

                return $notificationName === $eventName;
            });

        if ($notificationClass) {
            return app($notificationClass)->setEvent($event);
        }
    }

    protected function allEventClasses(): array
    {
        return [
            CronCheckFailedEvent::class,
            CronCheckRecoveredEvent::class,
        ];
    }
}
