<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Olt extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'vendor_id', 'device_model_id', 'site_id', 'firmware_profile_id',
        'name', 'hostname', 'ip_address', 'snmp_port', 'snmp_version',
        'snmp_community_read', 'snmp_community_write',
        'snmp_v3_username', 'snmp_v3_auth_pass', 'snmp_v3_auth_proto',
        'snmp_v3_priv_pass', 'snmp_v3_priv_proto',
        'ssh_username', 'ssh_password', 'ssh_port',
        'telnet_username', 'telnet_password', 'telnet_port',
        'status', 'last_polled_at', 'last_seen_at', 'system_info',
        'polling_interval', 'is_polling_enabled',
    ];

    protected $hidden = [
        'snmp_community_read', 'snmp_community_write',
        'snmp_v3_auth_pass', 'snmp_v3_priv_pass',
        'ssh_password', 'telnet_password',
    ];

    protected function casts(): array
    {
        return [
            'system_info' => 'array',
            'is_polling_enabled' => 'boolean',
            'last_polled_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'snmp_community_read' => 'encrypted',
            'snmp_community_write' => 'encrypted',
            'snmp_v3_auth_pass' => 'encrypted',
            'snmp_v3_priv_pass' => 'encrypted',
            'ssh_password' => 'encrypted',
            'telnet_password' => 'encrypted',
        ];
    }

    // ──── Relationships ────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function firmwareProfile(): BelongsTo
    {
        return $this->belongsTo(FirmwareProfile::class);
    }

    public function ponPorts(): HasMany
    {
        return $this->hasMany(PonPort::class);
    }

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class);
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

    public function scopePollingEnabled($query)
    {
        return $query->where('is_polling_enabled', true);
    }

    // ──── Helpers ────

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function getVendorCode(): string
    {
        return $this->vendor?->code ?? 'generic';
    }

    public function getOnuCount(): int
    {
        return $this->onus()->count();
    }

    public function getOnlineOnuCount(): int
    {
        return $this->onus()->where('status', 'online')->count();
    }
}
