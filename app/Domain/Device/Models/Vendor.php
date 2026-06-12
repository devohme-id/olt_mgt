<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'code', 'enterprise_oid', 'default_config', 'is_active'];

    protected function casts(): array
    {
        return [
            'default_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function deviceModels(): HasMany
    {
        return $this->hasMany(DeviceModel::class);
    }

    public function oidMappings(): HasMany
    {
        return $this->hasMany(VendorOidMapping::class);
    }
}
