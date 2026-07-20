<?php

namespace Database\Factories;

use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rate>
 */
class RateFactory extends Factory
{
    protected $model = Rate::class;

    public function definition(): array
    {
        return [
            'card_name' => fake()->randomElement(['Amazon', 'Apple', 'Google Play', 'Steam', 'eBay']),
            'usd_ngn' => fake()->randomFloat(2, 1400, 1600),
            'gbp_ngn' => fake()->randomFloat(2, 1700, 1950),
            'eur_ngn' => fake()->randomFloat(2, 1500, 1750),
        ];
    }
}
