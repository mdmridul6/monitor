<?php

namespace Vendor\LaravelMonitor\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Vendor\LaravelMonitor\LaravelMonitorServiceProvider;
use Tests\TestCase;

class LaravelMonitorServiceProviderTest extends TestCase
{
    public function test_service_provider_bootstrap_commands()
    {
        $provider = new LaravelMonitorServiceProvider($this->app);
        $provider->boot();

        // Check that the commands were registered
        $this->assertTrue($this->app->has('monitor:status'));
        $this->assertTrue($this->app->has('monitor:heartbeat'));
    }

    public function test_service_provider_merges_config()
    {
        $provider = new LaravelMonitorServiceProvider($this->app);
        $provider->register();

        // The config should be merged
        $this->assertTrue($this->app->config->has('monitor.enabled'));
    }
}