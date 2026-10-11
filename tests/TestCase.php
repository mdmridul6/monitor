<?php

namespace LaravelMonitor\Tests;

use Illuminate\Foundation\Application;
use LaravelMonitor\LaravelMonitorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelMonitorServiceProvider::class,
        ];
    }

    protected function defineApplication($app): Application
    {
        $app->detectEnvironment(function () {
            return 'testing';
        });

        return $app;
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('monitor.enabled', true);
        $app['config']->set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        $app['config']->set('monitor.api_token', 'test-token-12345');
    }
}