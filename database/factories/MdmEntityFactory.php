<?php

namespace Database\Factories;

use App\Models\MdmEntity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MdmEntity>
 */
class MdmEntityFactory extends Factory
{
    protected $model = MdmEntity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => fake()->randomElement(['customer', 'product', 'supplier', 'location', 'category']),
            'source_system' => fake()->randomElement(['manual', 'erp', 'crm', 'excel', 'api']),
            'external_id' => (string) fake()->unique()->randomNumber(5),
            'data' => [
                'name' => fake()->words(3, true),
                'value' => fake()->randomFloat(2, 0, 1000),
                'active' => fake()->boolean(),
            ],
            'normalized_data' => [
                'name' => fake()->words(3, true),
                'value' => fake()->randomFloat(2, 0, 1000),
                'active' => fake()->boolean(),
            ],
            'is_master' => fake()->boolean(20),
            'version' => fake()->numberBetween(1, 10),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
