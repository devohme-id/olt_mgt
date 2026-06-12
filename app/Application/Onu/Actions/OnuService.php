<?php

namespace App\Application\Onu\Actions;

use App\Domain\Device\Models\Onu;
use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\VlanConfig;
use App\Domain\Identity\Models\AuditLog;
use App\Infrastructure\DeviceAdapters\DeviceAdapterFactory;
use Illuminate\Support\Facades\DB;

class OnuService
{
    public function __construct(
        private DeviceAdapterFactory $adapterFactory,
    ) {}

    /**
     * Get paginated ONU list with filters.
     */
    public function list(array $filters = [])
    {
        $query = Onu::with(['olt', 'ponPort', 'serviceProfile']);

        if (!empty($filters['olt_id'])) {
            $query->where('olt_id', $filters['olt_id']);
        }
        if (!empty($filters['pon_port_id'])) {
            $query->where('pon_port_id', $filters['pon_port_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['auth_status'])) {
            $query->where('auth_status', $filters['auth_status']);
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('serial_number', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_id', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('onu_index', 'like', "%{$s}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'onu_index';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($filters['per_page'] ?? 25);
    }

    /**
     * Get ONU detail with all relationships and recent metrics.
     */
    public function detail(Onu $onu): array
    {
        $onu->load(['olt.vendor', 'ponPort', 'serviceProfile', 'vlanConfigs']);

        $opticalHistory = DB::table('onu_optical_history')
            ->where('onu_id', $onu->id)
            ->where('time', '>=', now()->subHours(24))
            ->orderBy('time', 'desc')
            ->limit(100)
            ->get();

        $recentEvents = DB::table('onu_events')
            ->where('onu_id', $onu->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return [
            'onu' => $onu,
            'opticalHistory' => $opticalHistory,
            'recentEvents' => $recentEvents,
        ];
    }

    /**
     * Update ONU info (customer details, description, profile).
     */
    public function update(Onu $onu, array $data, ?string $userId = null): Onu
    {
        $before = $onu->toArray();
        $onu->update($data);
        $this->audit('update', $onu, $before, $onu->fresh()->toArray(), $userId);
        return $onu->fresh(['olt', 'ponPort', 'serviceProfile']);
    }

    /**
     * Provision ONU — register via CLI/SNMP on OLT.
     */
    public function provision(Onu $onu, array $params, ?string $userId = null): array
    {
        $olt = $onu->olt;
        $adapter = $this->adapterFactory->create($olt);
        $result = $adapter->registerOnu($params);

        if ($result['success'] ?? false) {
            $onu->update([
                'auth_status' => 'authorized',
                'registered_at' => now(),
            ]);
            $this->audit('provision', $onu, null, $params, $userId);
        }

        return $result;
    }

    /**
     * Configure VLAN on ONU.
     */
    public function configureVlan(Onu $onu, array $vlanData, ?string $userId = null): array
    {
        $olt = $onu->olt;
        $adapter = $this->adapterFactory->create($olt);
        $result = $adapter->configureVlan($onu->onu_index, $vlanData);

        if ($result['success'] ?? false) {
            VlanConfig::updateOrCreate(
                ['onu_id' => $onu->id, 'vlan_id' => $vlanData['vlan_id']],
                [
                    'vlan_mode'    => $vlanData['vlan_mode'] ?? 'access',
                    'service_type' => $vlanData['service_type'] ?? 'internet',
                    'cos_priority' => $vlanData['cos_priority'] ?? 0,
                ]
            );
            $this->audit('configure_vlan', $onu, null, $vlanData, $userId);
        }

        return $result;
    }

    /**
     * Apply bandwidth profile to ONU.
     */
    public function applyProfile(Onu $onu, string $profileId, ?string $userId = null): array
    {
        $profile = \App\Domain\Device\Models\ServiceProfile::findOrFail($profileId);
        $olt = $onu->olt;
        $adapter = $this->adapterFactory->create($olt);

        $result = $adapter->applyProfile($onu->onu_index, [
            'up'   => $profile->upstream_bw_kbps,
            'down' => $profile->downstream_bw_kbps,
        ]);

        if ($result['success'] ?? false) {
            $onu->update(['service_profile_id' => $profileId]);
            $this->audit('apply_profile', $onu, null, ['profile' => $profile->name], $userId);
        }

        return $result;
    }

    /**
     * Reboot ONU.
     */
    public function reboot(Onu $onu, ?string $userId = null): array
    {
        $adapter = $this->adapterFactory->create($onu->olt);
        $result = $adapter->rebootOnu($onu->onu_index);
        $this->audit('reboot', $onu, null, $result, $userId);
        return $result;
    }

    /**
     * Delete ONU (soft delete + remove from OLT if needed).
     */
    public function delete(Onu $onu, bool $removeFromOlt = false, ?string $userId = null): array
    {
        $result = ['success' => true, 'message' => 'ONU deleted from NMS'];

        if ($removeFromOlt) {
            $adapter = $this->adapterFactory->create($onu->olt);
            $result = $adapter->deleteOnu($onu->onu_index);
        }

        $this->audit('delete', $onu, $onu->toArray(), null, $userId);
        $onu->delete();

        return $result;
    }

    /**
     * Get real-time optical data for a single ONU.
     */
    public function getOptical(Onu $onu): array
    {
        $adapter = $this->adapterFactory->create($onu->olt);
        return $adapter->getOnuOptical($onu->onu_index);
    }

    private function audit(string $action, Onu $onu, ?array $before, ?array $after, ?string $userId): void
    {
        AuditLog::create([
            'user_id'      => $userId ?? auth()->id(),
            'action'       => $action,
            'module'       => 'onu',
            'entity_type'  => Onu::class,
            'entity_id'    => $onu->id,
            'before_state' => $before,
            'after_state'  => $after,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
