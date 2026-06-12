<?php

namespace App\Domain\Alarm\Models;

use App\Domain\Device\Models\Onu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnuEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['onu_id', 'event_type', 'severity', 'data', 'created_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function onu(): BelongsTo
    {
        return $this->belongsTo(Onu::class);
    }
}
