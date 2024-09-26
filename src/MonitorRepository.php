<?php

namespace JkOster\CronMonitor;

use Illuminate\Support\Collection;
use JkOster\CronMonitor\Models\Monitor;
use JkOster\CronMonitor\Exceptions\InvalidConfiguration;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;

class MonitorRepository
{
    public static function getUnchecked(): Collection
    {
        $monitors = self::query()->where('status', CronMonitorStatus::UNKNOWN)->get();

        return MonitorCollection::make($monitors)->sortByName();
    }


    public static function getEnabled(): Collection
    {
        $monitors = self::query()->get();

        return MonitorCollection::make($monitors)->sortByHost();
    }

    public static function getDisabled(): Collection
    {
        $modelClass = static::determineMonitorModel();

        $monitors = $modelClass::where('enabled', false)->get();

        return MonitorCollection::make($monitors)->sortByHost();
    }

    public static function getForUptimeCheck(): MonitorCollection
    {
        $monitors = self::query()->get()->filter->shouldCheckUptime();

        return MonitorCollection::make($monitors)->sortByHost();
    }

    public static function getHealthy(): Collection
    {
        $monitors = self::query()->get()->filter->isHealthy();

        return MonitorCollection::make($monitors)->sortByHost();
    }

    public static function getWithFailingUptimeCheck(): Collection
    {
        $monitors = self::query()
            ->where('status', CronMonitorStatus::DOWN)
            ->get();

        return MonitorCollection::make($monitors)->sortByHost();
    }

    public static function getUnhealthy(): Collection
    {
        $monitors = self::query()->get()->reject->isHealthy();

        return MonitorCollection::make($monitors)->sortByHost();
    }

    protected static function query()
    {
        $modelClass = static::determineMonitorModel();

        return $modelClass::enabled();
    }

    protected static function determineMonitorModel(): string
    {
        $monitorModel = config('cron-monitor.monitor_model') ?? Monitor::class;

        if (! is_a($monitorModel, Monitor::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($monitorModel);
        }

        return $monitorModel;
    }
}
