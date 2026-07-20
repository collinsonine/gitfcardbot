<?php

namespace Database\Factories;

use App\Models\CardAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardAlias>
 */
class CardAliasFactory extends Factory
{
    protected $model = CardAlias::class;

    public function definition(): array
    {
        return [
            'alias_word' => fake()->unique()->word(),
            'resolved_card' => fake()->randomElement(['Amazon', 'Apple', 'Google Play', 'Steam', 'eBay']),
            'hit_count' => fake()->numberBetween(1, 50),
        ];
    }
}
