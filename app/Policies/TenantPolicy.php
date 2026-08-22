<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin() || $user->membershipIn($tenant) !== null;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        $membership = $user->membershipIn($tenant);

        return $user->isSuperAdmin() || $membership?->hasRole('admin');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }
}
