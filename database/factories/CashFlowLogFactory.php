<?php

namespace Database\Factories;

use App\Models\CashFlowLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashFlowLog>
 */
class CashFlowLogFactory extends Factory
{
    protected $model = CashFlowLog::class;

    public function definition(): array
    {
        return [
            'trade_id' => null,
            'type' => fake()->randomElement(['cash_out', 'revenue', 'capital_injection', 'expense']),
            'amount' => fake()->randomFloat(2, 100, 500000),
            'description' => fake()->sentence(),
        ];
    }
}
