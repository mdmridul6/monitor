<?php

namespace LaravelMonitor\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringClient
{
    public function __construct(protected \Illuminate\Contracts\Foundation\Application $app)
    {
    }

    public function send(array $payload): bool
    {
        $endpoint = $this->endpoint();
        $token = $this->token();
        $timeout = (int) $this->config('timeout', 5);

        if (empty($endpoint) || empty($token)) {
            Log::debug('Laravel Monitor: endpoint or token is not configured.');
            return false;
        }

        $this->validateEndpoint($endpoint);

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'Laravel-Monitor/1.0.0',
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Laravel Monitor: API returned a non-successful response.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Laravel Monitor: connection timed out or failed.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('Laravel Monitor: unexpected error during heartbeat transmission.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function validateEndpoint(string $endpoint): void
    {
        $isProduction = $this->app['config']->get('app.env') === 'production';
        $warnHttp = (bool) $this->config('warn_http_endpoint', true);

        if (str_starts_with($endpoint, 'http://')) {
            if ($isProduction && $warnHttp) {
                Log::warning('Laravel Monitor: Using an insecure HTTP endpoint in a production environment is discouraged. Please switch to HTTPS.');
            }
        }
    }

    protected function endpoint(): ?string
    {
        return $this->config('endpoint');
    }

    protected function token(): ?string
    {
        return $this->config('api_token');
    }

    protected function config(string $key, $default = null)
    {
        return $this->app['config']->get("monitor.{$key}", $default);
    }
}
