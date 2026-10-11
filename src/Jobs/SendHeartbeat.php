<?php

namespace LaravelMonitor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaravelMonitor\Http\MonitoringClient;

class SendHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $payload)
    {
        $this->tries = (int) config('monitor.max_retries', 3);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = new MonitoringClient(app());

        $success = $client->send($this->payload);

        if (!$success) {
            $this->failOrRetry();
        }
    }

    protected function failOrRetry(): void
    {
        $delay = (int) config('monitor.retry_delay', 60);

        if ($this->attempts() < $this->tries) {
            $this->release($delay);
        }
    }
}
