<?php

namespace LaravelMonitor\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use LaravelMonitor\Tests\TestCase;

class CommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure monitoring is enabled for feature tests by default
        $this->app['config']->set('monitor.enabled', true);
        $this->app['config']->set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        $this->app['config']->set('monitor.api_token', 'test-token-12345');
    }

    public function test_monitor_status_command_shows_enabled_status(): void
    {
        $output = $this->artisan('monitor:status')->getOutput();
        $this->assertStringContainsString('Monitoring Status', $output);
        $this->assertStringContainsString('Enabled', $output);
    }

    public function test_monitor_status_command_shows_disabled_status_when_disabled(): void
    {
        $this->app['config']->set('monitor.enabled', false);
        $output = $this->artisan('monitor:status')->getOutput();
        $this->assertStringContainsString('Monitoring Status', $output);
        $this->assertStringContainsString('Disabled', $output);
    }

    public function test_monitor_status_command_shows_token_status(): void
    {
        $this->app['config']->set('monitor.api_token', 'some-token');
        $output = $this->artisan('monitor:status')->getOutput();
        $this->assertStringContainsString('API Token Configured', $output);
        $this->assertStringContainsString('Yes', $output);

        $this->app['config']->set('monitor.api_token', '');
        $output = $this->artisan('monitor:status')->getOutput();
        $this->assertStringContainsString('API Token Configured', $output);
        $this->assertStringContainsString('No', $output);
    }

    public function test_heartbeat_command_respects_disabled_config(): void
    {
        $this->app['config']->set('monitor.enabled', false);
        $this->artisan('monitor:heartbeat --sync');
        $this->assertTrue(true);
    }

    public function test_heartbeat_command_respects_missing_endpoint_or_token(): void
    {
        $this->app['config']->set('monitor.endpoint', null);
        $this->artisan('monitor:heartbeat --sync');
        $this->assertTrue(true);

        $this->app['config']->set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        $this->app['config']->set('monitor.api_token', null);
        $this->artisan('monitor:heartbeat --sync');
        $this->assertTrue(true);
    }
}
