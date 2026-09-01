<?php

namespace App\Providers;

use App\Models\Integration;
use App\Models\MdmEntity;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\IntegrationPolicy;
use App\Policies\MdmEntityPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings for tenant-scoped resources.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Tenant::class => TenantPolicy::class,
        User::class => UserPolicy::class,
        MdmEntity::class => MdmEntityPolicy::class,
        Integration::class => IntegrationPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Whether the user belongs to the given tenant (platform admins always pass).
        Gate::define('tenant-access', function (User $user, Tenant $tenant): bool {
            return $user->isSuperAdmin() || $user->membershipIn($tenant) !== null;
        });

        // Whether the user holds the tenant-level "admin" role (or is platform admin).
        Gate::define('tenant-admin', function (User $user, Tenant $tenant): bool {
            $membership = $user->membershipIn($tenant);

            return $user->isSuperAdmin() || $membership?->hasRole('admin');
        });
    }
}
