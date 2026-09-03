<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TenantInvitation>
 */
class TenantInvitationFactory extends Factory
{
    protected $model = TenantInvitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(64),
            'roles' => ['user'],
            'permissions' => [],
            'invited_by' => User::factory(),
            'accepted_at' => fake()->randomElement([null, now(), fake()->dateTime()]),
            'expires_at' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }
}
