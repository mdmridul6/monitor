<?php

namespace Vendor\LaravelMonitor\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelMonitor\Support\ApplicationIdentifier;

class MonitorStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:status
                            {--show-id : Show the application ID}
                            {--test-connection : Test connection to monitoring endpoint}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of the Laravel monitoring package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Laravel Monitor Package Status');
        $this->line('');

        // Check if monitoring is enabled
        $enabled = config('monitor.enabled', false);
        $this->info('Monitoring Enabled: ' . ($enabled ? 'Yes' : 'No'));

        if ($enabled) {
            $this->line('');
            $this->info('Configuration:');
            $this->line('  Endpoint: ' . (config('monitor.endpoint') ?: 'Not configured'));
            $this->line('  Token: ' . (config('monitor.api_token') ? '[SET]' : 'Not configured'));
            $this->line('  Timeout: ' . config('monitor.timeout', 5) . ' seconds');
            $this->line('  Send Database Metadata: ' . (config('monitor.send_database_metadata', true) ? 'Yes' : 'No'));
            $this->line('  Send Environment: ' . (config('monitor.send_environment', true) ? 'Yes' : 'No'));
            $this->line('  Send Version Information: ' . (config('monitor.send_version_information', true) ? 'Yes' : 'No'));
            $this->line('  Heartbeat Interval: ' . config('monitor.heartbeat_interval', 5) . ' minutes');
            $this->line('  Retry Attempts: ' . (config('monitor.retry_attempts', 3)));
            $this->line('  Retry Delay: ' . (config('monitor.retry_delay', 10)) . ' seconds');
        }

        if ($this->option('show-id')) {
            $this->line('');
            $this->info('Application ID: ' . ApplicationIdentifier::getId());
        }

        if ($this->option('test-connection')) {
            $this->line('');
            $this->testConnection();
        }

        return Command::SUCCESS;
    }

    /**
     * Test connection to the monitoring endpoint.
     *
     * @return void
     */
    protected function testConnection()
    {
        if (!config('monitor.enabled', false)) {
            $this->error('Monitoring is disabled. Enable it in config/monitor.php or via APP_MONITORING_ENABLED=true');
            return;
        }

        if (!config('monitor.endpoint')) {
            $this->error('Monitoring endpoint is not configured.');
            return;
        }

        if (!config('monitor.api_token')) {
            $this->error('Monitoring API token is not configured.');
            return;
        }

        $this->info('Testing connection to monitoring endpoint...');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('monitor.timeout', 5))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('monitor.api_token'),
                    'Content-Type' => 'application/json',
                ])
                ->get(config('monitor.endpoint') . '/ping'); // Assuming a ping endpoint exists for testing

            if ($response->successful()) {
                $this->info('Connection successful! Response: ' . $response->body());
            } else {
                $this->warning('Connection failed with status: ' . $response->status());
                $this->line('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Connection test failed: ' . $e->getMessage());
        }
    }
}