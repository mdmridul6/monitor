<?php

namespace Vendor\LaravelMonitor;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Vendor\LaravelMonitor\Support\ApplicationIdentifier;
use Vendor\LaravelMonitor\Http\MonitoringClient;

class MonitorManager
{
    protected $config;
    protected $httpClient;
    protected $queue;

    public function __construct($config = null, $http = null, $queue = null)
    {
        $this->config = $config ?: app('config');
        $this->httpClient = $http ?: app()->make(\Illuminate\Http\Client\Factory::class);
        $this->queue = $queue ?: app('queue');
    }

    /**
     * Send a heartbeat to the monitoring server.
     *
     * @return void
     */
    public static function sendHeartbeatNow()
    {
        // Resolve the monitor manager from the container
        $monitor = app('monitor');

        // Send the heartbeat asynchronously via queue if configured
        if (config('monitor.queue_connection')) {
            dispatch(new \Vendor\LaravelMonitor\Jobs\SendHeartbeat())
                ->onConnection(config('monitor.queue_connection'));
        } else {
            // Send synchronously (but with timeout handling)
            $monitor->sendHeartbeat();
        }
    }

    /**
     * Send a heartbeat to the monitoring server.
     *
     * @return void
     */
    public function sendHeartbeat()
    {
        // Check if monitoring is enabled
        if (!$this->config->get('monitor.enabled', false)) {
            return;
        }

        // Validate required configuration
        if (!$this->config->get('monitor.endpoint') || !$this->config->get('monitor.api_token')) {
            Log::warning('Laravel Monitor: Missing required configuration (endpoint or api_token)');
            return;
        }

        try {
            // Prepare the telemetry payload
            $payload = $this->prepareTelemetryPayload();

            // Send the heartbeat request
            $response = $this->httpClient->timeout($this->config->get('monitor.timeout', 5))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config->get('monitor.api_token'),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config->get('monitor.endpoint'), $payload);

            // Log successful transmission
            if ($response->successful()) {
                Log::info('Laravel Monitor: Heartbeat sent successfully', [
                    'application_id' => $payload['application_id'],
                    'response_status' => $response->status(),
                ]);
            } else {
                Log::warning('Laravel Monitor: Heartbeat request failed', [
                    'application_id' => $payload['application_id'],
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't let it break the application
            Log::error('Laravel Monitor: Failed to send heartbeat', [
                'application_id' => ApplicationIdentifier::getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Prepare the telemetry payload to be sent to the monitoring server.
     *
     * @return array
     */
    protected function prepareTelemetryPayload()
    {
        $payload = [
            'application_id' => ApplicationIdentifier::getId(),
        ];

        // Add domain information if enabled
        if (config('monitor.send_environment', true)) {
            $payload['domain'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $payload['environment'] = app()->environment();
        }

        // Add version information if enabled
        if (config('monitor.send_version_information', true)) {
            $payload['app_name'] = config('app.name', 'Laravel Application');
            $payload['laravel_version'] = app()->version();
            $payload['php_version'] = PHP_VERSION;
            $payload['package_version'] = $this->getPackageVersion();
        }

        // Add database metadata if enabled
        if (config('monitor.send_database_metadata', true)) {
            $payload['database'] = $this->getDatabaseMetadata();
        }

        return $payload;
    }

    /**
     * Get database connection metadata.
     *
     * @return array
     */
    protected function getDatabaseMetadata()
    {
        $config = config('database.connections.' . config('database.default', 'mysql'));

        // Never include sensitive information like passwords
        return [
            'driver' => $config['driver'] ?? $config['name'] ?? 'unknown',
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'database' => $config['database'] ?? '',
        ];
    }

    /**
     * Get the package version from composer.json.
     *
     * @return string
     */
    protected function getPackageVersion()
    {
        $composerJsonPath = base_path('vendor/vendor/laravel-monitor/composer.json');

        if (file_exists($composerJsonPath)) {
            $composerJson = json_decode(file_get_contents($composerJsonPath), true);
            return $composerJson['version'] ?? 'unknown';
        }

        // Fallback for development
        return 'dev-main';
    }
}