<?php

namespace JkOster\CronMonitor;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JkOster\CronMonitor\Commands\ProcessCronCheck;
use JkOster\CronMonitor\Commands\SyncFile;
use JkOster\CronMonitor\Notifications\NotificationsEventHandler;

class CronMonitorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../config/cron-monitor.php' => config_path('cron-monitor.php'),
            ], 'config');
        }

        if (! class_exists('CreateCronMonitorsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_cron_monitors_table.php.stub' => database_path('migrations/'.$timestamp.'_create_cron_monitors_table.php'),
            ], 'migrations');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cron-monitor.php', 'cron-monitor');

        if (config('cron-monitor.api.enabled')) {
            $this->registerApiRoutes();
        }

        if (config('cron-monitor.notifications.enabled')) {
            $this->app['events']->subscribe(NotificationsEventHandler::class);
        }

        $this->app->bind('command.cron-monitor:process-checks', ProcessCronCheck::class);
        $this->app->bind('command.cron-monitor:sync-file', SyncFile::class);

        $this->commands([
            'command.cron-monitor:process-checks',
            'command.cron-monitor:sync-file',
        ]);
    }

    protected function registerApiRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function routeConfiguration()
    {
        $config = [
            'middleware' => config('cron-monitor.api.middleware'),
        ];

        if (config('cron-monitor.api.prefix')) {
            $config['prefix'] = config('cron-monitor.api.prefix');
        }

        return $config;
    }
}
