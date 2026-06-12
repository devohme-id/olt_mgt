<?php

namespace App\Domain\Alarm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['alarm_id', 'action', 'user_id', 'notes', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function alarm(): BelongsTo
    {
        return $this->belongsTo(Alarm::class);
    }
}
