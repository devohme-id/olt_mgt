<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOidMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'vendor_id', 'device_model_id', 'firmware_profile_id',
        'metric_key', 'oid', 'data_type', 'parser_class',
        'unit', 'multiplier', 'transform_rules', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'transform_rules' => 'array',
            'multiplier' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function firmwareProfile(): BelongsTo
    {
        return $this->belongsTo(FirmwareProfile::class);
    }
}
