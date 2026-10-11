<?php

namespace LaravelMonitor\Commands;

use Illuminate\Console\Command;
use LaravelMonitor\MonitorManager;

class SendHeartbeatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:heartbeat {--sync : Send the heartbeat synchronously without queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send application heartbeat telemetry to the monitoring server';

    /**
     * Execute the console command.
     */
    public function handle(MonitorManager $monitor): int
    {
        if ($this->option('sync')) {
            $monitor->sendSync();
        } else {
            $monitor->sendHeartbeat();
        }

        return Command::SUCCESS;
    }
}
