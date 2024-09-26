<?php

namespace JkOster\CronMonitor\Commands;

use JkOster\CronMonitor\CronMonitorRepository;

class ProcessCronCheck extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron-monitor:process-checks
                            {--f|force : Force run all enabled monitors not only due for checks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks all enabled CronMonitors if application is up or down';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        /** @var \JkOster\CronMonitor\CronMonitorCollection $cronMonitors */
        $cronMonitors = CronMonitorRepository::getEnabled();

        $this->comment('Start checking health of '.count($cronMonitors).' cron monitors...');

        $cronMonitors->checkHealth($force);

        $healthyMonitors = CronMonitorRepository::getHealthy();
        $this->info('Found '.count($healthyMonitors).' healthy cron monitors');

        $unhealthyMonitors = CronMonitorRepository::getUnhealthy();
        $this->info('Found '.count($unhealthyMonitors).' unhealthy cron monitors');

        $this->info('All done!');
    }
}
