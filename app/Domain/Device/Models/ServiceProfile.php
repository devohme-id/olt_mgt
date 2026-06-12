<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'description', 'upstream_bw_kbps', 'downstream_bw_kbps',
        'service_type', 'vlan_config', 'pppoe_config', 'qos_config', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'vlan_config' => 'array',
            'pppoe_config' => 'array',
            'qos_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class);
    }

    public function getSpeedLabel(): string
    {
        $down = $this->downstream_bw_kbps >= 1000
            ? ($this->downstream_bw_kbps / 1000) . ' Mbps'
            : $this->downstream_bw_kbps . ' Kbps';
        $up = $this->upstream_bw_kbps >= 1000
            ? ($this->upstream_bw_kbps / 1000) . ' Mbps'
            : $this->upstream_bw_kbps . ' Kbps';
        return "{$down}/{$up}";
    }
}
