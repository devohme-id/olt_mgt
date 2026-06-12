<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VlanConfig extends Model
{
    use HasUuids;

    protected $fillable = ['onu_id', 'vlan_id', 'vlan_mode', 'service_type', 'cos_priority'];

    public function onu(): BelongsTo
    {
        return $this->belongsTo(Onu::class);
    }
}
