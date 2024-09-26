<?php

namespace JkOster\CronMonitor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JkOster\CronMonitor\CronMonitor
 */
class CronMonitor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JkOster\CronMonitor\CronMonitor::class;
    }
}
