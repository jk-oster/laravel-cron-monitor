<?php

namespace JkOster\CronMonitor\Notifications\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use JkOster\CronMonitor\Models\CronMonitor;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;
use JkOster\CronMonitor\Notifications\BaseNotification;
use Spatie\UptimeMonitor\Helpers\Period;

class CronCheckRecovered extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public CronMonitor $monitor, public Period $downtimePeriod) {}

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->success()
            ->subject($this->getMessageText())
            ->line($this->getMessageText());

        foreach ($this->getAddtionalLines() as $value) {
            $mailMessage->line($value);
        }

        return $mailMessage;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->success()
            ->attachment(function (SlackAttachment $attachment) {
                $attachment
                    ->title($this->getMessageText())
                    ->content(implode("\n", $this->getAddtionalLines()))
                    ->fallback(implode("\n", $this->getAddtionalLines()))
                    ->timestamp(Carbon::now());
            });
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     */
    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content($this->getMessageText()."\n".implode("\n", $this->getAddtionalLines()))
            ->unicode();
    }

    public function toArray(object $notifiable): array
    {
        return $this->monitor->toArray();
    }

    public function isStillRelevant(): bool
    {
        return $this->monitor->status == CronMonitorStatus::UP;
    }

    protected function getMessageText(bool $withEmoji = true): string
    {
        return ($withEmoji ? "{$this->monitor->status_as_emoji} " : '')."{$this->monitor->name} has recovered after {$this->downtimePeriod->duration()}";
    }

    protected function getAddtionalLines(): array
    {
        return [
            "Downtime: {$this->downtimePeriod->duration()}: {$this->downtimePeriod->toText()}",
            "See the [report page]({$this->monitor->report_url}) or edit this check [here]({$this->monitor->editurl}).",
        ];
    }
}