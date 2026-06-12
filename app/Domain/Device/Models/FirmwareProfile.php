<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmwareProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_model_id', 'version', 'hardware_version',
        'supported_features', 'cli_command_set', 'oid_overrides', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'supported_features' => 'array',
            'cli_command_set' => 'array',
            'oid_overrides' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    /**
     * Get CLI command template for a given operation.
     */
    public function getCliCommand(string $operation): ?string
    {
        return $this->cli_command_set[$operation] ?? null;
    }

    /**
     * Check if a feature is supported by this firmware.
     */
    public function supportsFeature(string $feature): bool
    {
        return ($this->supported_features[$feature] ?? false) === true;
    }
}
