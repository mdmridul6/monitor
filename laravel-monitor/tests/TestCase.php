<?php

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Vendor\LaravelMonitor\LaravelMonitorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set default environment configurations
        $app['config']->set('monitor.enabled', false);
        $app['config']->set('monitor.endpoint', null);
        $app['config']->set('monitor.api_token', null);
        $app['config']->set('monitor.timeout', 5);
        $app['config']->set('monitor.send_database_metadata', true);
        $app['config']->set('monitor.send_environment', true);
        $app['config']->set('monitor.send_version_information', true);
        $app['config']->set('monitor.queue_connection', null);
        $app['config']->set('monitor.heartbeat_interval', 5);
        $app['config']->set('monitor.retry_attempts', 3);
        $app['config']->set('monitor.retry_delay', 10);
    }

    protected function resolveApplicationHttpClient($app)
    {
        return new \Illuminate\Http\Client\Factory(
            new \Illuminate\Http\Client\ConnectionFactory(
                $app->make(\Illuminate\Http\Client\ConnectionFactoryInterface::class)
            )
        );
    }
}