<?php

namespace App\Domain\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids, HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id',
        'is_active', 'last_login_at', 'preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function hasAnyRole(array $slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->role?->permissions()
            ->where('slug', $permissionSlug)
            ->exists() ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isNoc(): bool
    {
        return $this->hasRole('noc');
    }

    public function isEngineer(): bool
    {
        return $this->hasRole('engineer');
    }
}
