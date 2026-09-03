<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * All permissions enforced by the API.
     */
    public const ALL = [
        'create users',
        'update users',
        'delete users',
        'invite users',
        'manage integrations',
        'create mdm entities',
        'update mdm entities',
        'delete mdm entities',
        'normalize mdm entities',
        'run sync',
        'view audit logs',
        'view dashboard',
    ];

    public function run(): void
    {
        $guard = config('auth.defaults.guard') ?: 'web';

        $permissions = collect(self::ALL)
            ->map(fn (string $name) => Permission::findOrCreate($name, $guard))
            ->keyBy('name');

        // Platform-wide role assigned directly to users (HasRoles).
        $superAdmin = Role::findOrCreate('super-admin', $guard);
        $superAdmin->syncPermissions($permissions->keys()->all());

        // Tenant membership roles — referenced by tenant_users.role_id.
        $admin = Role::findOrCreate('admin', $guard);
        $admin->syncPermissions($permissions->keys()->all());

        $manager = Role::findOrCreate('manager', $guard);
        $manager->syncPermissions([
            'create mdm entities',
            'update mdm entities',
            'normalize mdm entities',
            'run sync',
            'view audit logs',
            'view dashboard',
        ]);

        $user = Role::findOrCreate('user', $guard);
        $user->syncPermissions([
            'view dashboard',
        ]);
    }
}
