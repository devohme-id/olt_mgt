<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'code', 'description'];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
