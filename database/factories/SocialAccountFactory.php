<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['github', 'google', 'gitlab', 'twitter', 'facebook']),
            'provider_id' => (string) fake()->unique()->randomNumber(8),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'avatar' => fake()->imageUrl(64, 64, 'people'),
            'token' => Str::random(80),
            'refresh_token' => Str::random(80),
            'expires_at' => fake()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
