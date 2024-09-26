<?php

namespace JkOster\CronMonitor\Exceptions;

use Exception;
use JkOster\CronMonitor\Models\Monitor;

class CannotSaveMonitor extends Exception
{
    public static function alreadyExists(Monitor $monitor): self
    {
        return new static("Could not save a monitor with name `{$monitor->name}` because there already exists another monitor with the same hash `{$monitor->hash}`. ".
            'Try saving a monitor with a different hash.');
    }
}
