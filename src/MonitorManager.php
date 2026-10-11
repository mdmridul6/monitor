<?php

namespace LaravelMonitor;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use LaravelMonitor\Http\MonitoringClient;
use LaravelMonitor\Jobs\SendHeartbeat;
use LaravelMonitor\Support\ApplicationIdentifier;
use LaravelMonitor\Support\PayloadBuilder;

class MonitorManager
{
    protected const CACHE_KEY_PREFIX = 'laravel-monitor:heartbeat:';
    protected const CACHE_TTL_MINUTES = 60;

    public function __construct(protected \Illuminate\Contracts\Foundation\Application $app)
    {
    }

    public function sendHeartbeat(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (empty($this->config('endpoint')) || empty($this->config('api_token'))) {
            Log::debug('Laravel Monitor: endpoint or token not configured, skipping heartbeat.');
            return;
        }

        $this->markSent();

        $payload = $this->buildPayload();

        $jobClass = SendHeartbeat::class;

        try {
            if (class_exists($jobClass) && method_exists($jobClass, 'handle')) {
                Queue::connection($this->config('queue_driver', 'default'))
                    ->push(new $jobClass($payload));
            } else {
                $this->sendSync($payload);
            }
        } catch (\Throwable $e) {
            Log::warning('Laravel Monitor: failed to dispatch heartbeat job.', [
                'error' => $e->getMessage(),
            ]);
            $this->sendSync($payload);
        }
    }

    public function sendSync(array $payload = null): void
    {
        $payload = $payload ?? $this->buildPayload();

        try {
            $client = new MonitoringClient($this->app);
            $client->send($payload);
        } catch (\Throwable $e) {
            Log::warning('Laravel Monitor: heartbeat failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function buildPayload(): array
    {
        return PayloadBuilder::make($this->app)->build();
    }

    public function getApplicationId(): string
    {
        return ApplicationIdentifier::make($this->app)->get();
    }

    public function recentlySent(): bool
    {
        $id = $this->getApplicationId();
        $cacheKey = self::CACHE_KEY_PREFIX . $id;

        return Cache::has($cacheKey);
    }

    public function lastSent(): int|false
    {
        $id = $this->getApplicationId();
        $cacheKey = self::CACHE_KEY_PREFIX . $id;

        return Cache::get($cacheKey) ?: false;
    }

    protected function markSent(): void
    {
        $id = $this->getApplicationId();
        $cacheKey = self::CACHE_KEY_PREFIX . $id;
        Cache::put($cacheKey, time(), \Carbon\Carbon::now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    protected function isEnabled(): bool
    {
        return (bool) $this->config('enabled', false);
    }

    protected function config(string $key, $default = null)
    {
        return $this->app['config']->get("monitor.{$key}", $default);
    }
}
