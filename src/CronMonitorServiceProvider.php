<?php

namespace JkOster\CronMonitor;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use JkOster\CronMonitor\Commands\ProcessCronCheck;
use JkOster\CronMonitor\Commands\SyncFile;
use Illuminate\Support\Facades\Route;


class CronMonitorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-cron-monitor')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_cron_monitor_table')
            // ->hasMigration('laravel_create_cron_monitor_table')
            // ->hasCommand(CronMonitorCommand::class)
            ->hasCommands([
                ProcessCronCheck::class,
                SyncFile::class
            ]);
    }

    protected function registerRoutes()
    {
        if(!config('cron-monitor.api.enabled')) {
            return;
        }

        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('cron-monitor.api.prefix'),
            'middleware' => config('cron-monitor.api.middleware'),
        ];
    }
}
