<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'domain' => fake()->unique()->domainName(),
            'database' => 'tenant_'.fake()->unique()->word(),
            'is_active' => fake()->boolean(90),
            'settings' => [
                'timezone' => 'UTC',
                'locale' => fake()->randomElement(['en', 'es', 'fr', 'de', 'pt', 'ja']),
                'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'BRL', 'JPY']),
                'plan' => fake()->randomElement(['free', 'starter', 'professional', 'enterprise']),
            ],
        ];
    }
}
