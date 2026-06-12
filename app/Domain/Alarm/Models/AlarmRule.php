<?php

namespace App\Domain\Alarm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlarmRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'metric_key', 'severity', 'condition_operator',
        'threshold_value', 'duration_seconds', 'cooldown_seconds',
        'device_type', 'is_active', 'notification_channels',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'decimal:4',
            'is_active' => 'boolean',
            'notification_channels' => 'array',
        ];
    }

    public function alarms(): HasMany
    {
        return $this->hasMany(Alarm::class);
    }

    /**
     * Evaluate if a metric value breaches this rule's threshold.
     */
    public function evaluate(float $value): bool
    {
        return match ($this->condition_operator) {
            'gt'  => $value > $this->threshold_value,
            'gte' => $value >= $this->threshold_value,
            'lt'  => $value < $this->threshold_value,
            'lte' => $value <= $this->threshold_value,
            'eq'  => $value == $this->threshold_value,
            'neq' => $value != $this->threshold_value,
            default => false,
        };
    }
}
