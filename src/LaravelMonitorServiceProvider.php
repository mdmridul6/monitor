<?php

namespace LaravelMonitor;

use Illuminate\Support\ServiceProvider;
use LaravelMonitor\Commands\MonitorStatusCommand;
use LaravelMonitor\Commands\SendHeartbeatCommand;

class LaravelMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/monitor.php',
            'monitor'
        );

        $this->app->singleton(MonitorManager::class, function ($app) {
            return new MonitorManager($app);
        });

        $this->app->alias(MonitorManager::class, 'laravel-monitor');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/monitor.php' => config_path('monitor.php'),
        ], 'monitoring-config');

        $this->commands([
            MonitorStatusCommand::class,
            SendHeartbeatCommand::class,
        ]);
    }
}
