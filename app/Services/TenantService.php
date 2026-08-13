<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Support\Str;

class TenantService
{
    public function createTenant(array $data): Tenant
    {
        return Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name'], '-'),
            'domain' => $data['domain'] ?? null,
            'database' => $data['database'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'settings' => $data['settings'] ?? [],
        ]);
    }

    public function switchTenant(User $user, Tenant $tenant): void
    {
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        session(['current_tenant_id' => $tenant->id]);
    }

    public function inviteUser(Tenant $tenant, string $email, array $roles = [], array $permissions = []): TenantInvitation
    {
        return TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'token' => Str::random(32),
            'roles' => $roles,
            'permissions' => $permissions,
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
