<?php

namespace LaravelMonitor\Tests\Feature;

use LaravelMonitor\Support\PayloadBuilder;
use LaravelMonitor\Tests\TestCase;

class PayloadBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('monitor.enabled', true);
        $this->app['config']->set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        $this->app['config']->set('monitor.api_token', 'test-token-12345');
    }

    public function test_payload_contains_required_fields(): void
    {
        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('application_id', $payload);
        $this->assertArrayHasKey('domain', $payload);
        $this->assertArrayHasKey('app_name', $payload);
        $this->assertArrayHasKey('environment', $payload);
        $this->assertArrayHasKey('laravel_version', $payload);
        $this->assertArrayHasKey('php_version', $payload);
        $this->assertArrayHasKey('package_version', $payload);
    }

    public function test_payload_includes_database_metadata_when_enabled(): void
    {
        $this->app['config']->set('monitor.send_database_metadata', true);
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test_db',
        ]);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('database', $payload);
        $this->assertArrayHasKey('driver', $payload['database']);
        $this->assertArrayHasKey('host', $payload['database']);
        $this->assertArrayHasKey('port', $payload['database']);
        $this->assertArrayHasKey('database', $payload['database']); // database name
    }

    public function test_payload_excludes_database_metadata_when_disabled(): void
    {
        $this->app['config']->set('monitor.send_database_metadata', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('database', $payload);
    }

    public function test_payload_includes_environment_when_enabled(): void
    {
        $this->app['config']->set('monitor.send_environment', true);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('environment', $payload);
        $this->assertNotEmpty($payload['environment']);
    }

    public function test_payload_excludes_environment_when_disabled(): void
    {
        $this->app['config']->set('monitor.send_environment', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('environment', $payload);
    }

    public function test_payload_includes_version_information_when_enabled(): void
    {
        $this->app['config']->set('monitor.send_version_information', true);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('laravel_version', $payload);
        $this->assertArrayHasKey('php_version', $payload);
        $this->assertArrayHasKey('package_version', $payload);
    }

    public function test_payload_excludes_version_information_when_disabled(): void
    {
        $this->app['config']->set('monitor.send_version_information', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('laravel_version', $payload);
        $this->assertArrayNotHasKey('php_version', $payload);
        $this->assertArrayNotHasKey('package_version', $payload);
    }

    public function test_payload_includes_git_info_when_enabled(): void
    {
        $this->app['config']->set('monitor.include_git_info', true);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('git', $payload);
    }

    public function test_payload_excludes_git_info_when_disabled(): void
    {
        $this->app['config']->set('monitor.include_git_info', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('git', $payload);
    }

    public function test_payload_includes_os_info_when_enabled(): void
    {
        $this->app['config']->set('monitor.include_os_info', true);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('os', $payload);
        $this->assertArrayHasKey('name', $payload['os']);
        $this->assertArrayHasKey('platform', $payload['os']);
        $this->assertArrayHasKey('arch', $payload['os']);
    }

    public function test_payload_excludes_os_info_when_disabled(): void
    {
        $this->app['config']->set('monitor.include_os_info', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('os', $payload);
    }

    public function test_payload_includes_hostname_when_enabled(): void
    {
        $this->app['config']->set('monitor.include_hostname', true);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('hostname', $payload);
        $this->assertNotEmpty($payload['hostname']);
    }

    public function test_payload_excludes_hostname_when_disabled(): void
    {
        $this->app['config']->set('monitor.include_hostname', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('hostname', $payload);
    }

    public function test_payload_includes_app_version_when_enabled(): void
    {
        $this->app['config']->set('monitor.include_app_version', true);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayHasKey('app_version', $payload);
    }

    public function test_payload_excludes_app_version_when_disabled(): void
    {
        $this->app['config']->set('monitor.include_app_version', false);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('app_version', $payload);
    }
}