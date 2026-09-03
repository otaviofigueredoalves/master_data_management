<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'login', 'logout', 'sync', 'restore']),
            'entity_type' => fake()->randomElement(['Tenant', 'User', 'TenantUser', 'MdmEntity', 'Integration', 'SyncJob', 'SocialAccount']),
            'entity_id' => fake()->numberBetween(1, 1000),
            'old_values' => [
                'name' => fake()->company(),
                'active' => fake()->boolean(),
            ],
            'new_values' => [
                'name' => fake()->company(),
                'active' => fake()->boolean(),
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
