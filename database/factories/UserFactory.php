<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'timezone' => fake()->timezone(),
            'locale' => fake()->randomElement(['en', 'es', 'fr', 'de', 'pt', 'pt_BR', 'ja', 'zh']),
            'last_login_at' => fake()->optional()->dateTime(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Attach the user as an active member of the given tenant.
     */
    public function memberOf(Tenant $tenant, string $role = 'user'): static
    {
        return $this->afterCreating(function (User $user) use ($tenant, $role): void {
            $roleId = Role::findOrCreate($role, 'web')->id;

            TenantUser::firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                ['role_id' => $roleId, 'is_active' => true, 'last_active_at' => now()],
            );

            if (! $user->current_tenant_id) {
                $user->forceFill(['current_tenant_id' => $tenant->id])->save();
            }
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
