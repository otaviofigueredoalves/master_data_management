<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'tenant_id',
    'current_tenant_id',
    'timezone',
    'locale',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The tenants this user is a member of.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users', 'user_id', 'tenant_id')
            ->withPivot('role_id', 'is_active', 'last_active_at')
            ->withTimestamps();
    }

    /**
     * Active memberships (soft-deleted excluded).
     */
    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * The tenant selected as the "current" working context.
     */
    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    /**
     * Membership of the user within a given tenant.
     */
    public function membershipIn(Tenant|int $tenant): ?TenantUser
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Whether the user is a platform-level super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
