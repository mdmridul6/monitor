<?php

namespace Vendor\LaravelMonitor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Vendor\LaravelMonitor\Jobs\SendHeartbeat;

class LaravelMonitorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/monitor.php' => config_path('monitor.php'),
        ], 'monitoring-config');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MonitorStatusCommand::class,
                Commands\SendHeartbeatCommand::class,
            ]);
        }

        // Schedule heartbeat to be sent periodically
        if (!$this->app->environment('testing')) {
            $this->app->booted(function () {
                $this->scheduleHeartbeat();
            });
        }
    }

    /**
     * Register services.
     */
    public function register()
    {
        // Merge configuration from the published config file
        $this->mergeConfigFrom(
            __DIR__.'/../config/monitor.php',
            'monitor'
        );

        // Bind the monitor manager into the service container
        $this->app->singleton('monitor', function ($app) {
            return new MonitorManager();
        });

        // Register the http service if it doesn't exist (for older Laravel versions or tests)
        if (!$this->app->bound('http')) {
            $this->app->singleton('http', function ($app) {
                return $app->make(\Illuminate\Http\Client\Factory::class);
            });
        }
    }

    /**
     * Schedule the heartbeat job to be sent periodically.
     */
    protected function scheduleHeartbeat()
    {
        // Send heartbeat on application boot
        $this->app->booted(function () {
            if (config('monitor.enabled', false)) {
                // Send immediate heartbeat on boot (queued)
                MonitorManager::sendHeartbeatNow();
            }
        });

        // Schedule periodic heartbeats (every 5 minutes by default)
        if ($this->app->runningInConsole()) {
            $schedule = $this->app->make('Illuminate\Console\Scheduling\Schedule');
            $schedule->call(function () {
                if (config('monitor.enabled', false)) {
                    MonitorManager::sendHeartbeatNow();
                }
            })->everyFiveMinutes()->withoutOverlapping()->name('laravel-monitor-heartbeat');
        }
    }
}