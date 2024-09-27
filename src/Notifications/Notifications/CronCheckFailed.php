<?php

namespace JkOster\CronMonitor\Notifications\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use JkOster\CronMonitor\Models\CronMonitor;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;
use JkOster\CronMonitor\Notifications\BaseNotification;

class CronCheckFailed extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public CronMonitor $monitor) {}

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->error()
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
            ->error()
            ->attachment(function (SlackAttachment $attachment) {
                $attachment
                    ->title($this->getMessageText())
                    ->content($this->getMessageText()."\n".implode("\n", $this->getAddtionalLines()))
                    ->fallback($this->getMessageText()."\n".implode("\n", $this->getAddtionalLines()))
                    ->timestamp(Carbon::now());
            });
    }

    public function toArray(object $notifiable): array
    {
        return $this->monitor->toArray();
    }

    public function isStillRelevant(): bool
    {
        return $this->monitor->status == CronMonitorStatus::DOWN;
    }

    protected function getMessageText(bool $withEmoji = true): string
    {
        return ($withEmoji ? "{$this->monitor->status_as_emoji} " : '')."{$this->monitor->name} seems down.";
    }

    protected function getDownTimeText(): string
    {
        return "Since: {$this->monitor->formattedLastCheckFailed('H:i d/m/Y')}";
    }

    protected function getFailureReasonText(): string
    {
        return "Failure reason: *{$this->monitor->failure_reason}*";
    }

    protected function getAddtionalLines(): array
    {
        return [
            $this->getDownTimeText(),
            $this->getFailureReasonText(),
        ];
    }
}
