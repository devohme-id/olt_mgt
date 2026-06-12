<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceModel extends Model
{
    use HasUuids;

    protected $fillable = [
        'vendor_id', 'name', 'model_number', 'device_type',
        'max_pon_ports', 'max_onus_per_port', 'capabilities',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'max_pon_ports' => 'integer',
            'max_onus_per_port' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function firmwareProfiles(): HasMany
    {
        return $this->hasMany(FirmwareProfile::class);
    }

    public function olts(): HasMany
    {
        return $this->hasMany(Olt::class);
    }
}
