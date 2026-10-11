<?php

namespace Vendor\LaravelMonitor\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Vendor\LaravelMonitor\Support\ApplicationIdentifier;

class MonitoringClient
{
    /**
     * Send heartbeat to monitoring server.
     *
     * @param array $payload
     * @return \Illuminate\Http\Client\Response
     */
    public static function send(array $payload)
    {
        // Validate required configuration
        if (!config('monitor.endpoint') || !config('monitor.api_token')) {
            Log::warning('Laravel Monitor: Missing required configuration (endpoint or api_token)');
            return null;
        }

        try {
            // Send the heartbeat request with timeout handling
            $response = Http::timeout(config('monitor.timeout', 5))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('monitor.api_token'),
                    'Content-Type' => 'application/json',
                ])
                ->post(config('monitor.endpoint'), $payload);

            // Log the result
            if ($response->successful()) {
                Log::info('Laravel Monitor: Heartbeat sent successfully', [
                    'application_id' => $payload['application_id'] ?? 'unknown',
                    'response_status' => $response->status(),
                ]);
            } else {
                Log::warning('Laravel Monitor: Heartbeat request failed', [
                    'application_id' => $payload['application_id'] ?? 'unknown',
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            // Log the error but don't let it break the application
            Log::error('Laravel Monitor: Failed to send heartbeat', [
                'application_id' => $payload['application_id'] ?? ApplicationIdentifier::getId() ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Validate that the monitoring endpoint uses HTTPS in production.
     *
     * @return void
     */
    public static function validateEndpointSecurity()
    {
        if (app()->environment('production')) {
            $endpoint = config('monitor.endpoint');

            if ($endpoint && str_starts_with($endpoint, 'http:')) {
                Log::warning('Laravel Monitor: Insecure HTTP endpoint detected in production environment. Please use HTTPS for secure communication.');
            }
        }
    }
}