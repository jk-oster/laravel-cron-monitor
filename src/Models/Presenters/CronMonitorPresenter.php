<?php

namespace JkOster\CronMonitor\Models\Presenters;

use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;

trait CronMonitorPresenter
{
    public function getStatusAsEmojiAttribute(): string
    {
        if ($this->status === CronMonitorStatus::UP) {
            return '✅ ';
        }

        if ($this->status === CronMonitorStatus::DOWN) {
            return '🚨 ';
        }

        if ($this->status === CronMonitorStatus::STARTED) {
            return '🕑 ';
        }

        return '❓ ';
    }

    public function formattedReportTitle(): string
    {
        return $this->getStatusAsEmojiAttribute().$this->name.' | Health Report';
    }

    public function formattedLastCheck(string $format = ''): string
    {
        return $this->formatDate('last_check', $format);
    }

    public function formattedLastCheckFailed(string $format = ''): string
    {
        return $this->formatDate('last_check_failed', $format);
    }

    protected function formatDate(string $attributeName, string $format = ''): string
    {
        if (! $this->$attributeName) {
            return '';
        }

        if ($format === 'forHumans') {
            return $this->$attributeName->diffForHumans();
        }

        if ($format === '') {
            $format = config('cron-monitor.notifications.date_format');
        }

        return $this->$attributeName->format($format);
    }
}
