<?php

namespace Database\Factories;

use App\Models\BotResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BotResponse>
 */
class BotResponseFactory extends Factory
{
    protected $model = BotResponse::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'message' => fake()->sentence(),
            'description' => fake()->sentence(),
        ];
    }
}
