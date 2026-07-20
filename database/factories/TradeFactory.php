<?php

namespace Database\Factories;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        $amountUsd = fake()->randomFloat(2, 10, 500);
        $ratePaid = fake()->randomFloat(2, 0.75, 0.95);
        $customerPayout = round($amountUsd * $ratePaid, 2);

        return [
            'user_id' => User::factory(),
            'card_type' => fake()->randomElement(['Amazon', 'Apple', 'Google Play', 'Steam', 'eBay']),
            'amount_usd' => $amountUsd,
            'rate_paid' => $ratePaid,
            'customer_payout' => $customerPayout,
            'estimated_profit' => round($amountUsd - $customerPayout, 2),
            'status' => 'pending',
        ];
    }
}
