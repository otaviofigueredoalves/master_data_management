<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company().' '.fake()->word(),
            'type' => fake()->randomElement(['salesforce', 'hubspot', 'sap', 'custom']),
            'credentials' => [
                'api_key' => Str::random(32),
                'endpoint' => fake()->url(),
                'client_id' => (string) fake()->randomNumber(5),
            ],
            'settings' => [
                'sync_interval' => fake()->numberBetween(5, 60),
                'direction' => fake()->randomElement(['inbound', 'outbound', 'bidirectional']),
                'batch_size' => fake()->numberBetween(10, 1000),
            ],
            'is_active' => fake()->boolean(90),
            'last_sync_at' => fake()->dateTime(),
        ];
    }
}
