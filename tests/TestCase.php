<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function seedTenantWithMembers(): array
    {
        $tenant = Tenant::factory()->create();

        $admin = User::factory()->memberOf($tenant, 'admin')->create();
        $manager = User::factory()->memberOf($tenant, 'manager')->create();
        $viewer = User::factory()->memberOf($tenant, 'user')->create();

        return compact('tenant', 'admin', 'manager', 'viewer');
    }

    protected function actingAsSanctum(User $user, array $abilities = ['*']): static
    {
        Sanctum::actingAs($user, $abilities);

        return $this;
    }

    protected function tenantHeaders(Tenant $tenant, ?User $user = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Tenant-ID' => (string) $tenant->id,
        ];

        if ($user) {
            $headers['Authorization'] = 'Bearer test-token';
        }

        return $headers;
    }

    protected function roleId(string $role): int
    {
        return Role::findByName($role, 'web')->id;
    }
}
