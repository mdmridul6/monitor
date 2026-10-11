<?php

namespace Vendor\LaravelMonitor\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelMonitor\Jobs\SendHeartbeat;

class SendHeartbeatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:heartbeat
                            {--sync : Send heartbeat synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually send a heartbeat to the monitoring server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!config('monitor.enabled', false)) {
            $this->error('Monitoring is disabled. Enable it in config/monitor.php or via APP_MONITORING_ENABLED=true');
            return Command::FAILURE;
        }

        if (!config('monitor.endpoint')) {
            $this->error('Monitoring endpoint is not configured.');
            return Command::FAILURE;
        }

        if (!config('monitor.api_token')) {
            $this->error('Monitoring API token is not configured.');
            return Command::FAILURE;
        }

        $this->info('Sending heartbeat to monitoring server...');

        if ($this->option('sync')) {
            // Send synchronously
            (new \Vendor\LaravelMonitor\MonitorManager())->sendHeartbeat();
            $this->info('Heartbeat sent synchronously.');
        } else {
            // Send via queue
            dispatch(new SendHeartbeat());
            $this->info('Heartbeat job queued for processing.');
        }

        return Command::SUCCESS;
    }
}