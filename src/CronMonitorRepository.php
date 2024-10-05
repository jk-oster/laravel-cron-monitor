<?php

namespace JkOster\CronMonitor;

use Illuminate\Support\Collection;
use JkOster\CronMonitor\Exceptions\InvalidConfiguration;
use JkOster\CronMonitor\Models\CronMonitor;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;

class CronMonitorRepository
{
    public static function getByHash(string $hash): ?CronMonitor
    {
        return self::query()->where('hash', '=', $hash)->first();
    }

    public static function getUnchecked(): Collection
    {
        $monitors = self::query()->where('status', CronMonitorStatus::UNKNOWN)->get();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    public static function getEnabled(): Collection
    {
        $monitors = self::query()->get();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    public static function getDisabled(): Collection
    {
        $modelClass = static::determineMonitorModel();

        $monitors = $modelClass::where('enabled', false)->get();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    public static function getForUptimeCheck(): CronMonitorCollection
    {
        $monitors = self::query()->get()->filter->shouldCheckUptime();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    public static function getHealthy(): Collection
    {
        $monitors = self::query()->get()->filter->isHealthy();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    public static function getWithFailingUptimeCheck(): Collection
    {
        $monitors = self::query()
            ->where('status', CronMonitorStatus::DOWN)
            ->get();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    public static function getUnhealthy(): Collection
    {
        $monitors = self::query()->get()->reject->isHealthy();

        return CronMonitorCollection::make($monitors)->sortByName();
    }

    protected static function query()
    {
        $modelClass = static::determineMonitorModel();

        return $modelClass::enabled();
    }

    protected static function determineMonitorModel(): string
    {
        $monitorModel = config('cron-monitor.monitor_model') ?? CronMonitor::class;

        if (! is_a($monitorModel, CronMonitor::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($monitorModel);
        }

        return $monitorModel;
    }
}
