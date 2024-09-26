<?php

namespace JkOster\CronMonitor\Exceptions;

use Exception;
use JkOster\CronMonitor\Models\CronMonitor;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className): self
    {
        return new static("The given model class `{$className}` does not extend `".CronMonitor::class.'`');
    }
}
