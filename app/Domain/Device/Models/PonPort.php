<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PonPort extends Model
{
    use HasUuids;

    protected $fillable = [
        'olt_id', 'port_index', 'port_name', 'port_type',
        'admin_status', 'oper_status', 'if_index', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'port_index' => 'integer',
            'if_index' => 'integer',
        ];
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class);
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
