<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\LinkDailyStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkDailyStat>
 */
class LinkDailyStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'date' => fake()->date(),
            'clicks' => 0,
        ];
    }
}
