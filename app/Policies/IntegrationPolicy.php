<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;

class IntegrationPolicy
{
    public function view(User $user, Integration $integration): bool
    {
        return $user->isSuperAdmin()
            || $user->membershipIn($integration->tenant) !== null;
    }

    public function create(User $user, ?Tenant $tenant = null): bool
    {
        if (! $tenant) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->membershipIn($tenant)?->hasPermissionTo('manage integrations');
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->isSuperAdmin()
            || $user->membershipIn($integration->tenant)?->hasPermissionTo('manage integrations');
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $this->update($user, $integration);
    }
}
