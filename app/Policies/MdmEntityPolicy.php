<?php

namespace App\Policies;

use App\Models\MdmEntity;
use App\Models\Tenant;
use App\Models\User;

class MdmEntityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MdmEntity $entity): bool
    {
        return $user->isSuperAdmin()
            || $user->membershipIn($entity->tenant) !== null;
    }

    public function create(User $user, ?Tenant $tenant = null): bool
    {
        if (! $tenant) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->membershipIn($tenant)?->hasPermissionTo('create mdm entities');
    }

    public function update(User $user, MdmEntity $entity): bool
    {
        return $user->isSuperAdmin()
            || $user->membershipIn($entity->tenant)?->hasPermissionTo('update mdm entities');
    }

    public function delete(User $user, MdmEntity $entity): bool
    {
        return $user->isSuperAdmin()
            || $user->membershipIn($entity->tenant)?->hasPermissionTo('delete mdm entities');
    }
}
