<?php

namespace App\Application\Olt\Actions;

use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\PonPort;
use App\Domain\Identity\Models\AuditLog;
use App\Infrastructure\DeviceAdapters\DeviceAdapterFactory;
use App\Infrastructure\Snmp\SnmpPollingService;
use Illuminate\Support\Facades\DB;

class OltService
{
    public function __construct(
        private DeviceAdapterFactory $adapterFactory,
        private SnmpPollingService $pollingService,
    ) {}

    /**
     * Create a new OLT and auto-create PON ports based on device model.
     */
    public function create(array $data, ?string $userId = null): Olt
    {
        return DB::transaction(function () use ($data, $userId) {
            $olt = Olt::create($data);

            // Auto-create PON ports based on device model
            $maxPorts = $olt->deviceModel?->max_pon_ports ?? 4;
            $portType = $olt->deviceModel?->device_type ?? 'epon';

            for ($i = 0; $i < $maxPorts; $i++) {
                PonPort::create([
                    'olt_id'     => $olt->id,
                    'port_index' => $i,
                    'port_name'  => strtoupper($portType) . " 0/{$i}",
                    'port_type'  => $portType,
                ]);
            }

            $this->audit('create', 'olt', $olt, null, $olt->toArray(), $userId);

            return $olt->load(['vendor', 'deviceModel', 'site', 'ponPorts']);
        });
    }

    /**
     * Update an existing OLT.
     */
    public function update(Olt $olt, array $data, ?string $userId = null): Olt
    {
        $before = $olt->toArray();
        $olt->update($data);
        $this->audit('update', 'olt', $olt, $before, $olt->fresh()->toArray(), $userId);

        return $olt->fresh(['vendor', 'deviceModel', 'site', 'ponPorts']);
    }

    /**
     * Delete an OLT (soft delete).
     */
    public function delete(Olt $olt, ?string $userId = null): void
    {
        $this->audit('delete', 'olt', $olt, $olt->toArray(), null, $userId);
        $olt->delete();
    }

    /**
     * Sync OLT — trigger immediate SNMP poll to refresh data.
     */
    public function sync(Olt $olt): array
    {
        $metrics = $this->pollingService->pollOltHealth($olt);

        if ($metrics['poll_success'] ?? false) {
            $olt->update([
                'status'         => 'online',
                'last_polled_at' => now(),
                'last_seen_at'   => now(),
                'system_info'    => array_filter([
                    'cpu_usage'    => $metrics['cpu_usage'] ?? null,
                    'memory_usage' => $metrics['memory_usage'] ?? null,
                    'temperature'  => $metrics['temperature'] ?? null,
                    'uptime'       => $metrics['uptime'] ?? null,
                ]),
            ]);
        } else {
            if ($olt->status !== 'maintenance') {
                $olt->update(['status' => 'offline', 'last_polled_at' => now()]);
            }
        }

        return $metrics;
    }

    /**
     * Sync ONU table from OLT — discover/update all ONUs.
     */
    public function syncOnus(Olt $olt): int
    {
        $onuData = $this->pollingService->pollOnuTable($olt);
        // Actual ONU upsert is handled by PollOnuJob, just dispatch it
        \App\Jobs\PollOnuJob::dispatch($olt->id);
        return count($onuData);
    }

    /**
     * Get OLT system info via adapter.
     */
    public function getSystemInfo(Olt $olt): array
    {
        $adapter = $this->adapterFactory->create($olt);
        return $adapter->getSystemInfo();
    }

    /**
     * Backup OLT running config.
     */
    public function backupConfig(Olt $olt): string
    {
        $adapter = $this->adapterFactory->create($olt);
        $config = $adapter->backupConfig();

        // Store backup to disk
        $filename = "config-backups/{$olt->id}/" . now()->format('Y-m-d_His') . '.cfg';
        \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $config);

        $this->audit('backup', 'olt', $olt, null, ['filename' => $filename], auth()->id());

        return $config;
    }

    /**
     * Reboot OLT via adapter.
     */
    public function reboot(Olt $olt, ?string $userId = null): array
    {
        $adapter = $this->adapterFactory->create($olt);
        $result = $adapter->rebootOlt();

        $this->audit('reboot', 'olt', $olt, null, $result, $userId);

        return $result;
    }

    /**
     * Get paginated OLT list with counts and filters.
     */
    public function list(array $filters = [])
    {
        $query = Olt::with(['vendor', 'deviceModel', 'site'])
            ->withCount([
                'onus',
                'onus as online_onus_count' => fn ($q) => $q->where('status', 'online'),
                'onus as offline_onus_count' => fn ($q) => $q->whereIn('status', ['offline', 'los']),
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }
        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get detailed OLT with all relationships.
     */
    public function detail(Olt $olt): Olt
    {
        return $olt->load([
            'vendor', 'deviceModel', 'site', 'firmwareProfile',
            'ponPorts' => fn ($q) => $q->withCount([
                'onus',
                'onus as online_onus_count' => fn ($q2) => $q2->where('status', 'online'),
            ])->orderBy('port_index'),
        ])->loadCount([
            'onus',
            'onus as online_onus_count' => fn ($q) => $q->where('status', 'online'),
            'onus as offline_onus_count' => fn ($q) => $q->whereIn('status', ['offline', 'los']),
        ]);
    }

    /**
     * Get latest metrics for an OLT.
     */
    public function getLatestMetrics(Olt $olt, int $hours = 24): array
    {
        $metrics = DB::table('olt_metrics')
            ->where('olt_id', $olt->id)
            ->where('time', '>=', now()->subHours($hours))
            ->orderBy('time', 'desc')
            ->limit(100)
            ->get();

        return $metrics->toArray();
    }

    private function audit(string $action, string $module, Olt $entity, ?array $before, ?array $after, ?string $userId): void
    {
        AuditLog::create([
            'user_id'      => $userId ?? auth()->id(),
            'action'       => $action,
            'module'       => $module,
            'entity_type'  => Olt::class,
            'entity_id'    => $entity->id,
            'before_state' => $before,
            'after_state'  => $after,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
