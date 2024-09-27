<?php

namespace JkOster\CronMonitor\Models\Traits;

use Carbon\Carbon;
use Cron\CronExpression;
use DateTime;
use Illuminate\Http\Request;
use JkOster\CronMonitor\Events\CronCheckFailedEvent;
use JkOster\CronMonitor\Events\CronCheckRecoveredEvent;
use JkOster\CronMonitor\Events\IncomingPingReceivedEvent;
use JkOster\CronMonitor\Exceptions\InvalidPingStatus;
use JkOster\CronMonitor\Helpers\Period;
use JkOster\CronMonitor\Models\CronMonitor;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;
use JkOster\CronMonitor\Models\Enums\IncomingPingStatus;

trait SupportsCronHealthCheck
{
    public static function bootSupportsMonitor(): void
    {
        static::saving(function (CronMonitor $monitor) {
            $tz = $monitor->timezone;
            $currentDateTime = new DateTime('now', $tz);

            if (is_null($monitor->status_last_change_date)) {
                $monitor->status_last_change_date = Carbon::now($tz);

                return;
            }

            if (is_null($monitor->next_due_date)) {
                $monitor->next_due_date = $monitor->calculateNextDueDateWithGracePeriod($currentDateTime, $tz);

                return;
            }

            if ($monitor->getOriginal('status') != $monitor->status) {
                $monitor->status_last_change_date = Carbon::now($tz);
            }

            $cronExpressionChanged = $monitor->getOriginal('cron_expression') != $monitor->cron_expression;
            $frequencyChanged = $monitor->getOriginal('frequency_in_minutes') != $monitor->frequency_in_minutes;
            $gracePeriodChanged = $monitor->getOriginal('grace_period_in_minutes') != $monitor->grace_period_in_minutes;

            if ($cronExpressionChanged || $frequencyChanged || $gracePeriodChanged) {
                $monitor->next_due_date = $monitor->calculateNextDueDateWithGracePeriod($currentDateTime, $tz);
            }
        });
    }

    public function shouldCheckDown(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->status == CronMonitorStatus::DOWN) {
            return false;
        }

        return $this->statusExpired();
    }

    public function statusExpired(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }

    public function calculateNextDueDate(?\DateTime $currentTime = null, \DateTimeZone|string|null $tz = null): \DateTime
    {
        $currentTime = $currentTime ? (new Carbon($currentTime))->setTimezone($tz) : Carbon::now($tz);
        if ($this->cron_expression && $this->last_check && CronExpression::isValidExpression($this->cron_expression)) {
            $cron = new CronExpression($this->cron_expression);

            return $cron->getNextRunDate($currentTime, $tz);
        } else {
            return $currentTime->addMinutes($this->frequency_in_minutes)->toDateTime();
        }
    }

    public function calculateNextDueDateWithGracePeriod(?\DateTime $currentTime = null, \DateTimeZone|string|null $tz = null): \DateTime
    {
        $nextDueDate = new Carbon($this->calculateNextDueDate($currentTime, $tz));

        return $nextDueDate->addMinutes($this->grace_period);
    }

    public function cronMonitorStatusReceived(string $incomingPingStatus, ?Request $request = null): void
    {
        $oldStatus = $this->status;

        $statusMapping = [
            IncomingPingStatus::SUCCESS => CronMonitorStatus::UP,
            IncomingPingStatus::ERROR => CronMonitorStatus::DOWN,
            IncomingPingStatus::UNKNOWN => CronMonitorStatus::UNKNOWN,
            IncomingPingStatus::STARTED => CronMonitorStatus::STARTED,
        ];

        if (! isset($statusMapping[$incomingPingStatus])) {
            throw InvalidPingStatus::receivedPingStatusIsInvalid($incomingPingStatus);
        }

        event(new IncomingPingReceivedEvent($this, $request->all() ?? []));

        $newStatus = $statusMapping[$incomingPingStatus];
        $tz = $this->timezone;
        $now = Carbon::now($tz);

        $this->update([
            'last_check_date' => $now,
            'status' => $newStatus,
            'last_ping_status' => $incomingPingStatus,
        ]);

        dd($oldStatus, $newStatus);

        $statusChanged = $oldStatus != $newStatus;
        $isCurrentlyInGracePeriod = $oldStatus == CronMonitorStatus::STARTED;
        $startsGracePeriod = $newStatus == CronMonitorStatus::STARTED;

        $isDown = $newStatus == CronMonitorStatus::DOWN;
        $isUp = $newStatus == CronMonitorStatus::UP;
        $isUnknown = $newStatus == CronMonitorStatus::UNKNOWN;

        if (! $isDown && ! $isUnknown) {
            $nextDueDate = $this->calculateNextDueDateWithGracePeriod($now->toDateTime(), $tz);
            $this->next_due_date = $nextDueDate;
        }

        if ($isDown || $isUnknown) {
            $this->setFailureReason($request);
        }

        if ($statusChanged && ! $startsGracePeriod) {
            if ($isDown) {
                $this->cronMonitorHasFailed($request);
            }
            if ($isUp && ! $isCurrentlyInGracePeriod) {
                $this->cronMonitorHasRecovered();
            }
        }

        if ($startsGracePeriod) {
            $this->cronMonitorGracePeriodStarted();
        }

        $this->save();
    }

    public function checkHealthStatus(?Carbon $now = null): string
    {
        $now = $now ?? Carbon::now($this->timezone);

        if (! $this->last_check) {

            if ($this->status != CronMonitorStatus::UNKNOWN) {
                $this->cronMonitorStatusUnknown();
            }

            return CronMonitorStatus::UNKNOWN;
        }

        if ($this->statusExpired()) {
            $this->cronMonitorHasFailed();
        }

        return $this->status;
    }

    public function cronMonitorHasFailed(?Request $request = null): void
    {
        $this->setFailureReason($request);
        $this->status = CronMonitorStatus::DOWN;
        $this->last_check_failed_date = Carbon::now($this->timezone);
        $this->save();

        event(new CronCheckFailedEvent($this));
    }

    public function setFailureReason(?Request $request = null): void
    {
        if ($request) {
            if ($request->getContent() != '') {
                $this->failure_reason = $request->getContent();

                return;
            }

            if ($request->has('reason')) {
                $this->failure_reason = $request->input('reason');

                return;
            }

            if ($request->has('message')) {
                $this->failure_reason = $request->input('message');

                return;
            }

            $this->failure_reason = 'application sent error ping';
        } else {
            $this->failure_reason = 'no ping received within required interval';
        }
    }

    public function cronMonitorHasRecovered(): void
    {
        $tz = $this->timezone;
        $this->status = CronMonitorStatus::UP;
        $this->save();

        if ($this->last_check_failed_date) {
            $lastStatusChangeDate = $this->last_check_failed_date;
            $downtimePeriod = new Period($lastStatusChangeDate, $this->last_check_date ?? Carbon::now($tz));

            event(new CronCheckRecoveredEvent($this, $downtimePeriod));
        }
    }

    public function cronMonitorGracePeriodStarted(): void
    {
        $tz = $this->timezone;
        $this->status = CronMonitorStatus::STARTED;
        $this->next_due_date = Carbon::now(config('app.timezone', 'UTC'))->addMinutes($this->grace_period_in_minutes);
        $this->save();
    }

    public function cronMonitorStatusUnknown(): void
    {
        $this->status = CronMonitorStatus::UNKNOWN;
        $this->save();
    }
}
