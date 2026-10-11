<?php

namespace LaravelMonitor\Tests\Unit;

use Illuminate\Support\Facades\Http;
use LaravelMonitor\Http\MonitoringClient;
use LaravelMonitor\Support\ApplicationIdentifier;
use LaravelMonitor\Support\PayloadBuilder;
use LaravelMonitor\Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_sensitive_database_details_are_not_included(): void
    {
        $this->app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'password' => 'super-secret-password',
            'username' => 'admin',
        ]);

        $payload = PayloadBuilder::make($this->app)->build();

        $this->assertArrayNotHasKey('password', $payload['database'] ?? []);
        $this->assertArrayNotHasKey('db_password', $payload['database'] ?? []);
        $this->assertArrayNotHasKey('username', $payload['database'] ?? []);
    }

    public function test_app_key_is_never_included(): void
    {
        $this->app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');

        $payload = PayloadBuilder::make($this->app)->build();

        $json = json_encode($payload);
        $this->assertStringNotContainsString('base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', $json);
        $this->assertArrayNotHasKey('app_key', $payload);
    }

    public function test_api_token_is_never_included_in_payload(): void
    {
        $token = 'my-super-secret-telemetry-token';
        $this->app['config']->set('monitor.api_token', $token);

        $payload = PayloadBuilder::make($this->app)->build();

        $json = json_encode($payload);
        $this->assertStringNotContainsString($token, $json);
        $this->assertArrayNotHasKey('api_token', $payload);
    }

    public function test_authorization_header_contains_token(): void
    {
        Http::fake();

        $token = 'test-token-12345';
        $this->app['config']->set('monitor.api_token', $token);

        $client = new MonitoringClient($this->app);
        $client->send(['application_id' => 'test-id']);

        Http::assertSent(function ($request) use ($token) {
            return $request->hasHeader('Authorization', "Bearer {$token}");
        });
    }
}
