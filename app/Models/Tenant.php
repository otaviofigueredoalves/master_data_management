<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'domain', 'database', 'is_active', 'settings'])]
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function mdmEntities(): HasMany
    {
        return $this->hasMany(MdmEntity::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(SyncJob::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }
}
