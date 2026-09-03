<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\SyncJob;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncJob>
 */
class SyncJobFactory extends Factory
{
    protected $model = SyncJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'integration_id' => Integration::factory(),
            'type' => fake()->randomElement(['initial_sync', 'incremental_sync', 'full_sync', 'delete_sync']),
            'status' => fake()->randomElement(['pending', 'running', 'completed', 'failed']),
            'started_at' => fn () => fake()->dateTime(),
            'completed_at' => fn (array $attributes) => in_array($attributes['status'], ['completed', 'failed'], true)
                ? fake()->dateTimeBetween($attributes['started_at'], '+1 hour')
                : null,
            'error_message' => fn (array $attributes) => $attributes['status'] === 'failed'
                ? fake()->sentence()
                : null,
            'payload' => [
                'entity_type' => fake()->randomElement(['customer', 'product', 'supplier']),
                'limit' => fake()->numberBetween(10, 500),
                'since' => fake()->date(),
            ],
            'result' => [
                'processed' => fake()->numberBetween(0, 500),
                'created' => fake()->numberBetween(0, 100),
                'updated' => fake()->numberBetween(0, 100),
                'errors' => fake()->numberBetween(0, 10),
            ],
        ];
    }

    /**
     * Indicate a pending sync job.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate a completed sync job.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHours(fake()->numberBetween(1, 6)),
            'completed_at' => now()->subHours(fake()->numberBetween(0, 5)),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate a failed sync job.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
            'completed_at' => fake()->dateTimeBetween($attributes['started_at'] ?? 'now', '+1 hour'),
        ]);
    }
}
