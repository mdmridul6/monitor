<?php

namespace Vendor\LaravelMonitor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelMonitor\MonitorManager;
use Vendor\LaravelMonitor\Support\ApplicationIdentifier;

class SendHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to retry the job before failing.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Send the heartbeat
        $monitor = app('monitor');
        $monitor->sendHeartbeat();
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // Log the failure but don't let it break the application
        \Illuminate\Support\Facades\Log::error('Laravel Monitor: Heartbeat job failed permanently', [
            'application_id' => ApplicationIdentifier::getId(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}