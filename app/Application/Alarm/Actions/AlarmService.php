<?php

namespace App\Application\Alarm\Actions;

use App\Domain\Alarm\Models\Alarm;
use App\Domain\Identity\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AlarmService
{
    public function __construct(
        private TelegramNotificationService $telegram,
    ) {}

    /**
     * Get paginated alarms with filters.
     */
    public function list(array $filters = [])
    {
        $query = Alarm::with(['olt', 'onu']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 25);
    }

    /**
     * Create a new alarm and optionally notify.
     */
    public function raise(string $entityType, int|string $entityId, string $severity, string $message, ?array $metricData = null): Alarm
    {
        // Check if there's already an active alarm for this entity with similar message
        $existing = Alarm::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'active')
            ->where('message', $message)
            ->first();

        if ($existing) {
            // Just update timestamp
            $existing->touch();
            return $existing;
        }

        $alarm = Alarm::create([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'olt_id'      => $entityType === 'olt' ? $entityId : ($entityType === 'onu' ? \App\Domain\Device\Models\Onu::find($entityId)?->olt_id : null),
            'onu_id'      => $entityType === 'onu' ? $entityId : null,
            'severity'    => $severity,
            'message'     => $message,
            'metric_data' => $metricData,
            'status'      => 'active',
        ]);

        // Send Notification
        $this->telegram->sendAlarmNotification($alarm);

        // Optional: Broadcast via Reverb
        // \App\Events\AlarmRaised::dispatch($alarm);

        return $alarm;
    }

    /**
     * Acknowledge an alarm.
     */
    public function acknowledge(Alarm $alarm, string $userId): Alarm
    {
        $alarm->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);

        $this->audit('acknowledge', $alarm, $userId);

        return $alarm;
    }

    /**
     * Resolve/Clear an alarm.
     */
    public function resolve(Alarm $alarm, string $userId, ?string $notes = null): Alarm
    {
        $alarm->update([
            'status' => 'cleared',
            'cleared_at' => now(),
            'notes' => $notes,
        ]);

        $this->audit('resolve', $alarm, $userId);

        return $alarm;
    }

    private function audit(string $action, Alarm $alarm, string $userId): void
    {
        AuditLog::create([
            'user_id'      => $userId,
            'action'       => $action,
            'module'       => 'alarm',
            'entity_type'  => Alarm::class,
            'entity_id'    => $alarm->id,
            'ip_address'   => request()->ip() ?? '127.0.0.1',
            'user_agent'   => request()->userAgent() ?? 'System',
        ]);
    }
}
