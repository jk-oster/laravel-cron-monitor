<?php

namespace JkOster\CronMonitor\Commands;

use Cron\CronExpression;
use JkOster\CronMonitor\Exceptions\CannotSaveMonitor;
use JkOster\CronMonitor\Models\Monitor;

class SyncFile extends BaseCommand
{
    protected $signature = 'cron-monitor:sync-file
                            {path : Path to JSON file with monitors}
                            {--delete-missing : Delete monitors from the database if they\'re not found in the monitors file}';

    protected $description = 'One way sync monitors from JSON file to database';

    public function handle()
    {
        $json = file_get_contents($this->argument('path'));

        $monitorsInFile = collect(json_decode($json, true));

        $this->validateMonitors($monitorsInFile);

        $this->createOrUpdateMonitorsFromFile($monitorsInFile);

        $this->deleteMissingMonitors($monitorsInFile);
    }

    protected function validateMonitors($monitorsInFile)
    {
        $monitorsInFile->each(function ($monitorAttributes) {
            if (isset($monitorAttributes['cron_expression']) && ! CronExpression::isValidExpression($monitorAttributes['cron_expression'])) {
                throw new CannotSaveMonitor("Cron Expression `{$monitorAttributes['cron_expression']}` is invalid");
            }
        });
    }

    protected function createOrUpdateMonitorsFromFile($monitorsInFile)
    {
        $monitorsInFile
            ->each(function ($monitorAttributes) {
                $this->createOrUpdateMonitor($monitorAttributes);
            });

        $this->info("Synced {$monitorsInFile->count()} monitor(s) to database");
    }

    protected function createOrUpdateMonitor(array $monitorAttributes)
    {
        Monitor::firstOrNew([
            'hash' => $monitorAttributes['hash'],
        ])
            ->fill($monitorAttributes)
            ->save();
    }

    protected function deleteMissingMonitors($monitorsInFile)
    {
        if (! $this->option('delete-missing')) {
            return;
        }

        Monitor::all()
            ->reject(function (Monitor $monitor) use ($monitorsInFile) {
                return $monitorsInFile->contains('hash', $monitor->hash);
            })
            ->each(function (Monitor $monitor) {
                $path = $this->argument('path');
                $this->comment("Deleted monitor for `{$monitor->name}` from database because no monitor with a matching hash was not found in `{$path}`");
                $monitor->delete();
            });
    }
}
