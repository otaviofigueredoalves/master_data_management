<?php

namespace Database\Seeders;

use App\Models\Integration;
use App\Models\MdmEntity;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $this->seedDemoTenant();
        $this->seedPlatformAdmin();
    }

    protected function seedDemoTenant(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'acme-corp'],
            [
                'name' => 'Acme Corporation',
                'is_active' => true,
                'settings' => [
                    'plan' => 'enterprise',
                    'locale' => 'en',
                    'timezone' => 'UTC',
                ],
            ],
        );

        $roleFor = fn (string $role) => Role::findByName($role, 'web');

        $users = [
            'demo@mdmsaas.test' => ['name' => 'Ana Owner', 'role' => 'admin'],
            'manager@mdmsaas.test' => ['name' => 'Bruno Manager', 'role' => 'manager'],
            'viewer@mdmsaas.test' => ['name' => 'Carla Viewer', 'role' => 'user'],
        ];

        foreach ($users as $email => $attributes) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $attributes['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            TenantUser::firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                [
                    'role_id' => $roleFor($attributes['role'])->id,
                    'is_active' => true,
                    'last_active_at' => now(),
                ],
            );

            if (! $user->current_tenant_id) {
                $user->forceFill(['current_tenant_id' => $tenant->id])->save();
            }
        }

        if (MdmEntity::where('tenant_id', $tenant->id)->doesntExist()) {
            MdmEntity::factory()->count(5)->create([
                'tenant_id' => $tenant->id,
                'created_by' => User::where('email', 'demo@mdmsaas.test')->first()->id,
                'updated_by' => User::where('email', 'demo@mdmsaas.test')->first()->id,
            ]);
        }

        Integration::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Salesforce CRM'],
            [
                'type' => 'salesforce',
                'credentials' => ['username' => 'demo', 'security_token' => 'encrypted-in-prod'],
                'settings' => ['direction' => 'bidirectional'],
                'is_active' => true,
            ],
        );
    }

    protected function seedPlatformAdmin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'platform@mdmsaas.test'],
            [
                'name' => 'Platform Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
    }
}
