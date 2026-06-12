<?php

namespace App\Jobs;

use App\Domain\Device\Models\Olt;
use App\Infrastructure\Snmp\SnmpPollingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollOltJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 10;

    public function __construct(
        public readonly string $oltId,
    ) {
        $this->onQueue(config('snmp.queue.poll_queue', 'snmp-polling'));
    }

    public function handle(SnmpPollingService $pollingService): void
    {
        $olt = Olt::with(['vendor', 'deviceModel', 'firmwareProfile'])->find($this->oltId);

        if (!$olt || !$olt->is_polling_enabled) {
            return;
        }

        Log::debug("Polling OLT: {$olt->name} ({$olt->ip_address})");

        $metrics = $pollingService->pollOltHealth($olt);

        if ($metrics['poll_success'] ?? false) {
            // Update OLT status
            $olt->update([
                'status' => 'online',
                'last_polled_at' => now(),
                'last_seen_at' => now(),
            ]);

            // Insert into metrics hypertable
            DB::table('olt_metrics')->insert([
                'time'           => now(),
                'olt_id'         => $olt->id,
                'cpu_usage'      => $metrics['cpu_usage'] ?? null,
                'memory_usage'   => $metrics['memory_usage'] ?? null,
                'temperature'    => $metrics['temperature'] ?? null,
                'uptime_seconds' => $metrics['uptime'] ?? null,
                'total_rx_bps'   => $metrics['total_rx_bps'] ?? null,
                'total_tx_bps'   => $metrics['total_tx_bps'] ?? null,
                'online_onus'    => $olt->getOnlineOnuCount(),
                'offline_onus'   => $olt->getOnuCount() - $olt->getOnlineOnuCount(),
            ]);

            // Broadcast realtime update
            event(new \App\Domain\Monitoring\Events\MetricsCollected('olt', $olt->id, $metrics));

        } else {
            // Mark OLT as offline if consecutive failures
            if ($olt->status !== 'maintenance') {
                $olt->update([
                    'status' => 'offline',
                    'last_polled_at' => now(),
                ]);

                event(new \App\Domain\Device\Events\OltStatusChanged($olt, 'offline'));
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("PollOltJob failed for OLT {$this->oltId}: {$exception->getMessage()}");
    }
}
