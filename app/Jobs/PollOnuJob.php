<?php

namespace App\Jobs;

use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\Onu;
use App\Domain\Device\Models\PonPort;
use App\Infrastructure\Snmp\SnmpPollingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly string $oltId,
    ) {
        $this->onQueue(config('snmp.queue.poll_queue', 'snmp-polling'));
    }

    public function handle(SnmpPollingService $pollingService): void
    {
        $olt = Olt::with(['vendor', 'deviceModel', 'firmwareProfile', 'ponPorts'])->find($this->oltId);

        if (!$olt || !$olt->is_polling_enabled || !$olt->isOnline()) {
            return;
        }

        Log::debug("Polling ONUs for OLT: {$olt->name}");

        $onuData = $pollingService->pollOnuTable($olt);

        if (empty($onuData)) {
            Log::info("No ONU data returned for OLT {$olt->name}");
            return;
        }

        $now = now();

        foreach ($onuData as $index => $data) {
            // Determine PON port from ONU index
            $ponPort = $this->resolvePonPort($olt, $index);

            if (!$ponPort) continue;

            // Upsert ONU record
            $onu = Onu::withTrashed()
                ->where('olt_id', $olt->id)
                ->where('onu_index', $index)
                ->first();

            $previousStatus = $onu?->status;

            $onuAttributes = [
                'olt_id'         => $olt->id,
                'pon_port_id'    => $ponPort->id,
                'onu_index'      => $index,
                'status'         => $data['status'] ?? 'unknown',
                'mac_address'    => $data['mac_address'] ?? $onu?->mac_address,
                'name'           => $data['name'] ?? $onu?->name,
                'device_type'    => $data['device_type'] ?? $onu?->device_type,
                'onu_type'       => $data['onu_type'] ?? $onu?->onu_type,
                'rx_power_dbm'   => $data['rx_power_dbm'],
                'tx_power_dbm'   => $data['tx_power_dbm'],
                'distance_meters' => $data['distance_meters'],
                'last_seen_at'   => $now,
            ];

            // Local tracking for deregistration
            $currentStatus = $data['status'] ?? 'unknown';
            if ($previousStatus === 'online' && in_array($currentStatus, ['offline', 'los'])) {
                $onuAttributes['deregistered_at'] = $now;
                $onuAttributes['deregister_reason'] = 'Local Status Change';
            } elseif ($currentStatus === 'online') {
                $onuAttributes['deregistered_at'] = null;
                $onuAttributes['deregister_reason'] = null;
            }

            if ($onu) {
                $onu->update($onuAttributes);
                if ($onu->trashed()) {
                    $onu->restore();
                }
            } else {
                $onu = Onu::create(array_merge($onuAttributes, [
                    'registered_at' => $now,
                    'auth_status' => 'authorized',
                ]));
                Log::info("New ONU discovered: {$onu->onu_index} on {$olt->name}");
            }

            // Calculate optical loss
            if ($data['tx_power_dbm'] !== null && $data['rx_power_dbm'] !== null) {
                $opticalLoss = abs($data['tx_power_dbm'] - ($data['rx_power_dbm'] ?? 0));
                $onu->update(['optical_loss_db' => round($opticalLoss, 2)]);
            }

            // Insert ONU metrics
            DB::table('onu_metrics')->insert([
                'time'             => $now,
                'onu_id'           => $onu->id,
                'olt_id'           => $olt->id,
                'rx_power_dbm'     => $data['rx_power_dbm'],
                'tx_power_dbm'     => $data['tx_power_dbm'],
                'distance_meters'  => $data['distance_meters'],
                'status'           => $this->statusToInt($data['status'] ?? 'unknown'),
            ]);

            // Insert optical history
            DB::table('onu_optical_history')->insert([
                'time'             => $now,
                'onu_id'           => $onu->id,
                'rx_power_dbm'     => $data['rx_power_dbm'],
                'tx_power_dbm'     => $data['tx_power_dbm'],
                'olt_rx_power_dbm' => $data['olt_rx_power_dbm'] ?? null,
            ]);

            // Detect status changes
            if ($previousStatus && $previousStatus !== ($data['status'] ?? 'unknown')) {
                event(new \App\Domain\Device\Events\OnuStatusChanged($onu, $previousStatus, $data['status']));
            }
        }

        // Broadcast dashboard update
        event(new \App\Domain\Monitoring\Events\MetricsCollected('onu_batch', $olt->id, [
            'onu_count' => count($onuData),
            'polled_at' => $now->toIso8601String(),
        ]));
    }

    /**
     * Resolve PON port from ONU index pattern.
     * HSGQ EPON index format: {slot}.{port}.{onu_id} → e.g., "0.1.5" = slot 0, port 1, onu 5
     */
    private function resolvePonPort(Olt $olt, string $onuIndex): ?PonPort
    {
        // Handle V-SOL / HSGQ style large integer index (e.g., 16777473)
        if (is_numeric($onuIndex) && strpos($onuIndex, '.') === false) {
            $devicePortId = ((int) $onuIndex >> 8) & 0xFF;
            $portIndex = max(0, $devicePortId - 1); // Device is 1-indexed, DB is 0-indexed
        } else {
            $parts = explode('.', $onuIndex);
            if (count($parts) >= 2) {
                $portIndex = (int) $parts[1]; // Second part is the PON port number
            } else {
                $portIndex = 0;
            }
        }

        return $olt->ponPorts->firstWhere('port_index', $portIndex)
            ?? $olt->ponPorts->first();
    }

    private function statusToInt(string $status): int
    {
        return match ($status) {
            'online' => 1,
            'offline' => 2,
            'los' => 3,
            'disabled' => 4,
            'power_off' => 5,
            default => 0,
        };
    }
}
