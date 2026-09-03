<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<TenantUser>
 */
class TenantUserFactory extends Factory
{
    protected $model = TenantUser::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'role_id' => $this->roleId('user'),
            'is_active' => true,
            'last_active_at' => now(),
        ];
    }

    public function withRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => $this->roleId($role),
        ]);
    }

    protected function roleId(string $role): int
    {
        return Role::findOrCreate($role, 'web')->id;
    }
}
