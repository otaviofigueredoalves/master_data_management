<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

#[Fillable(['tenant_id', 'user_id', 'role_id', 'is_active', 'last_active_at'])]
class TenantUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenant_users';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Spatie role catalog entry referenced by this membership.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    /**
     * Delegates the permission check to the role attached to this membership.
     */
    public function hasPermissionTo(string $permission): bool
    {
        return (bool) $this->role?->hasPermissionTo($permission);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
