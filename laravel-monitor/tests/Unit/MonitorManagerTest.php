<?php

namespace Vendor\LaravelMonitor\Tests\Unit;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Vendor\LaravelMonitor\MonitorManager;
use Tests\TestCase;
use Mockery as M;

class MonitorManagerTest extends TestCase
{
    public function test_monitor_manager_sends_heartbeat_when_enabled()
    {
        Config::set('monitor.enabled', true);
        Config::set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        Config::set('monitor.api_token', 'test-token');
        Config::set('monitor.timeout', 5);

        // Mock HTTP facade
        Http::fake([
            'https://monitor.example.com/api/v1/applications/heartbeat' => Http::response(['success' => true], 200),
        ]);

        // Mock Log facade to verify logging
        Log::shouldReceive('info')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'Heartbeat sent successfully');
        });

        $monitor = new MonitorManager();

        $monitor->sendHeartbeat();
    }

    public function test_monitor_manager_does_not_send_heartbeat_when_disabled()
    {
        Config::set('monitor.enabled', false);

        // Mock HTTP facade
        Http::fake();

        // Mock Log facade to verify no logging occurs
        Log::shouldReceive('info')->never();
        Log::shouldReceive('warning')->never();
        Log::shouldReceive('error')->never();

        $monitor = new MonitorManager();

        $monitor->sendHeartbeat();
    }

    public function test_monitor_manager_handles_missing_configuration_gracefully()
    {
        Config::set('monitor.enabled', true);
        Config::set('monitor.endpoint', null);
        Config::set('monitor.api_token', null);

        // Mock Log facade to verify warning is logged
        Log::shouldReceive('warning')->once()->withArgs(function ($message) {
            return str_contains($message, 'Missing required configuration');
        });


        $monitor = new MonitorManager();

        $monitor->sendHeartbeat(); // Should not throw exception
    }

    public function test_monitor_manager_handles_http_exceptions_gracefully()
    {
        Config::set('monitor.enabled', true);
        Config::set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        Config::set('monitor.api_token', 'test-token');
        Config::set('monitor.timeout', 5);

        // Mock HTTP facade to throw an exception
        Http::fake([
            'https://monitor.example.com/api/v1/applications/heartbeat' => function () {
                throw new \Exception('Network error');
            },
        ]);

        // Mock Log facade to verify error is logged
        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'Failed to send heartbeat');
        });


        $monitor = new MonitorManager();

        $monitor->sendHeartbeat(); // Should not throw exception
    }

    public function test_prepare_telemetry_payload_includes_correct_data()
    {
        Config::set('monitor.send_database_metadata', true);
        Config::set('monitor.send_environment', true);
        Config::set('monitor.send_version_information', true);
        Config::set('app.name', 'Test Application');

        // Mock HTTP facade
        Http::fake([
            'https://monitor.example.com/api/v1/applications/heartbeat' => Http::response(['success' => true], 200),
        ]);

        $monitor = new MonitorManager();

        // Access protected method via reflection
        $reflection = new \ReflectionMethod($monitor, 'prepareTelemetryPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($monitor);

        $this->assertArrayHasKey('application_id', $payload);
        $this->assertArrayHasKey('domain', $payload);
        $this->assertArrayHasKey('environment', $payload);
        $this->assertArrayHasKey('app_name', $payload);
        $this->assertArrayHasKey('laravel_version', $payload);
        $this->assertArrayHasKey('php_version', $payload);
        $this->assertArrayHasKey('package_version', $payload);
        $this->assertArrayHasKey('database', $payload);

        // Check database structure
        $this->assertArrayHasKey('driver', $payload['database']);
        $this->assertArrayHasKey('host', $payload['database']);
        $this->assertArrayHasKey('port', $payload['database']);
        $this->assertArrayHasKey('database', $payload['database']);
    }

    public function test_prepare_telemetry_payload_excludes_disabled_sections()
    {
        Config::set('monitor.send_database_metadata', false);
        Config::set('monitor.send_environment', false);
        Config::set('monitor.send_version_information', false);

        // Mock HTTP facade
        Http::fake([
            'https://monitor.example.com/api/v1/applications/heartbeat' => Http::response(['success' => true], 200),
        ]);

        $monitor = new MonitorManager();

        // Access protected method via reflection
        $reflection = new \ReflectionMethod($monitor, 'prepareTelemetryPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($monitor);

        // Should only have application_id when all sections are disabled
        $this->assertArrayHasKey('application_id', $payload);
        $this->assertCount(1, $payload); // Only application_id
    }
}