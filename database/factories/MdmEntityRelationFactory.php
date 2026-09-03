<?php

namespace Database\Factories;

use App\Models\MdmEntity;
use App\Models\MdmEntityRelation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MdmEntityRelation>
 */
class MdmEntityRelationFactory extends Factory
{
    protected $model = MdmEntityRelation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'parent_id' => MdmEntity::factory(),
            'child_id' => MdmEntity::factory(),
            'relation_type' => fake()->randomElement([
                'belongs_to',
                'has_many',
                'related',
                'duplicate',
                'master_child',
            ]),
            'metadata' => [
                'strength' => fake()->randomFloat(2, 0, 1),
                'confidence' => fake()->randomFloat(2, 0, 1),
                'rule' => fake()->word(),
            ],
        ];
    }
}
