<?php

namespace JkOster\CronMonitor;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class MonitorCollection extends Collection
{
    public function checkHealth($force = false): void
    {
        $now = Carbon::now();

        foreach ($this->items as $monitor) {

            /** @var CronMonitor $monitor */
            
            if ($force || $monitor->shouldCheckDown()) {
                $monitor->checkHealthStatus($now);
            }
        }
    }

    public function sortByName(): self
    {
        return $this->sortBy('name');
    }
}
