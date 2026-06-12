<?php

namespace App\Domain\Alarm\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alarm extends Model
{
    use HasUuids;

    protected $fillable = [
        'alarm_rule_id', 'device_id', 'device_type', 'severity', 'status',
        'message', 'context', 'acknowledged_by', 'acknowledged_at',
        'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function alarmRule(): BelongsTo
    {
        return $this->belongsTo(AlarmRule::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AlarmHistory::class);
    }

    // ──── Scopes ────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    // ──── Actions ────

    public function acknowledge(string $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
        $this->histories()->create([
            'action' => 'acknowledged',
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    public function resolve(string $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
        $this->histories()->create([
            'action' => 'resolved',
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }
}
