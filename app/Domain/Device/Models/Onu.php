<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Onu extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'olt_id', 'pon_port_id', 'service_profile_id',
        'onu_index', 'serial_number', 'mac_address', 'vendor_id', 'model',
        'firmware_version', 'status', 'auth_status', 'description',
        'customer_name', 'customer_id', 'distance_meters',
        'rx_power_dbm', 'tx_power_dbm', 'olt_rx_power_dbm', 'optical_loss_db',
        'last_seen_at', 'registered_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'distance_meters' => 'integer',
            'rx_power_dbm' => 'decimal:2',
            'tx_power_dbm' => 'decimal:2',
            'olt_rx_power_dbm' => 'decimal:2',
            'optical_loss_db' => 'decimal:2',
            'last_seen_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    // ──── Relationships ────

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort(): BelongsTo
    {
        return $this->belongsTo(PonPort::class);
    }

    public function serviceProfile(): BelongsTo
    {
        return $this->belongsTo(ServiceProfile::class);
    }

    public function vlanConfigs(): HasMany
    {
        return $this->hasMany(VlanConfig::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(\App\Domain\Alarm\Models\OnuEvent::class);
    }

    // ──── Scopes ────

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeLos($query)
    {
        return $query->where('status', 'los');
    }

    public function scopeByOlt($query, string $oltId)
    {
        return $query->where('olt_id', $oltId);
    }

    // ──── Helpers ────

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isLos(): bool
    {
        return $this->status === 'los';
    }

    public function hasLowRxPower(float $threshold = -27.0): bool
    {
        return $this->rx_power_dbm !== null && $this->rx_power_dbm < $threshold;
    }

    public function hasCriticalRxPower(float $threshold = -30.0): bool
    {
        return $this->rx_power_dbm !== null && $this->rx_power_dbm < $threshold;
    }

    public function getOpticalLoss(): ?float
    {
        if ($this->tx_power_dbm !== null && $this->olt_rx_power_dbm !== null) {
            return round($this->tx_power_dbm - $this->olt_rx_power_dbm, 2);
        }
        return $this->optical_loss_db;
    }
}
