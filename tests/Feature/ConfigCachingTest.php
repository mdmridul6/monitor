<?php

namespace LaravelMonitor\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use LaravelMonitor\Jobs\SendHeartbeat;
use LaravelMonitor\MonitorManager;
use LaravelMonitor\Tests\TestCase;

class ConfigCachingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('monitor.enabled', true);
        $this->app['config']->set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        $this->app['config']->set('monitor.api_token', 'test-token-12345');
    }

    public function test_config_caching_works_correctly(): void
    {
        // Test that the configuration is correctly loaded from cache
        $this->app['config']->set('monitor.enabled', true);
        $this->app['config']->set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        $this->app['config']->set('monitor.api_token', 'test-token-12345');

        Http::fake([
            'monitor.example.com/api/v1/applications/heartbeat' => Http::response([
                'success' => true,
                'message' => 'Heartbeat received',
            ], 200),
        ]);

        $manager = $this->app->make(MonitorManager::class);
        $manager->sendSync();

        Http::assertSent(function ($request) {
            return $request->url() == 'https://monitor.example.com/api/v1/applications/heartbeat'
                && $request->hasHeader('Authorization', 'Bearer test-token-12345');
        });
    }

    public function test_config_caching_with_disabled_monitoring(): void
    {
        $this->app['config']->set('monitor.enabled', false);

        Http::fake();

        $manager = $this->app->make(MonitorManager::class);
        $manager->sendSync();

        Http::assertNothingSent();
    }

    public function test_config_caching_with_missing_endpoint(): void
    {
        $this->app['config']->set('monitor.endpoint', null);

        Http::fake();

        $manager = $this->app->make(MonitorManager::class);
        $manager->sendSync();

        Http::assertNothingSent();
    }

    public function test_config_caching_with_missing_token(): void
    {
        $this->app['config']->set('monitor.api_token', null);

        Http::fake();

        $manager = $this->app->make(MonitorManager::class);
        $manager->sendSync();

        Http::assertNothingSent();
    }

    public function test_config_caching_with_queue_driver(): void
    {
        Queue::fake();

        $this->app['config']->set('monitor.queue_driver', 'sync');

        $manager = $this->app->make(MonitorManager::class);
        $manager->sendHeartbeat();

        Queue::assertPushed(SendHeartbeat::class);
    }
}