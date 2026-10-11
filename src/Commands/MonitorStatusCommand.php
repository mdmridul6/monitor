<?php

namespace LaravelMonitor\Commands;

use Illuminate\Console\Command;
use LaravelMonitor\MonitorManager;

class MonitorStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current status and configuration of Laravel Monitor';

    /**
     * Execute the console command.
     */
    public function handle(MonitorManager $monitor): int
    {
        $enabled = config('monitor.enabled', false) ? 'Enabled' : 'Disabled';
        $endpoint = config('monitor.endpoint') ?: 'Not Configured';
        $hasToken = !empty(config('monitor.api_token')) ? 'Yes' : 'No';

        $this->line('Monitoring Status: ' . $enabled);
        $this->line('API Endpoint: ' . $endpoint);
        $this->line('API Token Configured: ' . $hasToken);
        $this->line('Application ID: ' . $monitor->getApplicationId());

        $lastSent = $monitor->lastSent();
        $lastSentFormatted = $lastSent
            ? \Carbon\Carbon::createFromTimestamp($lastSent)->diffForHumans()
            : 'Never';

        $this->line('Last Heartbeat Sent: ' . $lastSentFormatted);

        return Command::SUCCESS;
    }
}
