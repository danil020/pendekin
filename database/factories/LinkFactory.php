<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => fake()->unique()->bothify('??????'),
            'original_url' => fake()->url(),
            'is_alias' => false,
            'is_active' => true,
            'expires_at' => null,
        ];
    }
}
