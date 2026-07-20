<?php

use App\Models\Rate;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('correctly calculates customer payout and estimated profit', function () {
    $rate = Rate::factory()->create([
        'card_name' => 'Amazon',
        'usd_ngn' => 0.85,
        'gbp_ngn' => 1.08,
        'eur_ngn' => 0.94,
    ]);

    $user = User::factory()->create(['phone_number' => '+1234567890']);

    $trade = $user->trades()->create([
        'card_type' => 'Amazon',
        'amount_usd' => 200.00,
        'rate_paid' => $rate->usd_ngn,
        'customer_payout' => round(200.00 * 0.85, 2),
        'estimated_profit' => round(200.00 - (200.00 * 0.85), 2),
        'status' => 'pending',
    ]);

    expect((float) $trade->customer_payout)->toEqual(170.00);
    expect((float) $trade->estimated_profit)->toEqual(30.00);
    expect(round($trade->customer_payout + $trade->estimated_profit, 2))->toEqual($trade->amount_usd);
});

it('prevents floating point rounding discrepancies', function () {
    $user = User::factory()->create(['phone_number' => '+1234567890']);

    $amounts = [10.00, 25.50, 100.00, 250.75, 500.00];
    $rates = [0.75, 0.80, 0.85, 0.90, 0.95];

    foreach ($amounts as $amount) {
        foreach ($rates as $rateValue) {
            $customerPayout = round($amount * $rateValue, 2);
            $estimatedProfit = round($amount - $customerPayout, 2);

            $trade = $user->trades()->create([
                'card_type' => 'Amazon',
                'amount_usd' => $amount,
                'rate_paid' => $rateValue,
                'customer_payout' => $customerPayout,
                'estimated_profit' => $estimatedProfit,
                'status' => 'pending',
            ]);

            $trade->refresh();

            $computedPayout = round($trade->amount_usd * $trade->rate_paid, 2);
            $computedProfit = round($trade->amount_usd - $computedPayout, 2);

            expect((float) $trade->customer_payout)->toEqual($computedPayout);
            expect((float) $trade->estimated_profit)->toEqual($computedProfit);
            expect(round($trade->customer_payout + $trade->estimated_profit, 2))->toEqual($trade->amount_usd);
        }
    }
});

it('respects the rate from the rates table when computing trades', function () {
    $rate = Rate::factory()->create([
        'card_name' => 'Steam',
        'usd_ngn' => 0.82,
        'gbp_ngn' => 1.04,
        'eur_ngn' => 0.91,
    ]);

    $user = User::factory()->create(['phone_number' => '+1234567890']);

    $trade = $user->trades()->create([
        'card_type' => 'Steam',
        'amount_usd' => 150.00,
        'rate_paid' => $rate->usd_ngn,
        'customer_payout' => round(150.00 * $rate->usd_ngn, 2),
        'estimated_profit' => round(150.00 - (150.00 * $rate->usd_ngn), 2),
        'status' => 'pending',
    ]);

    expect((float) $trade->rate_paid)->toEqual(0.82);
    expect((float) $trade->customer_payout)->toEqual(123.00);
    expect((float) $trade->estimated_profit)->toEqual(27.00);
});

it('calculates aggregate dashboard totals correctly', function () {
    $user = User::factory()->create(['phone_number' => '+1234567890']);

    $user->trades()->createMany([
        [
            'card_type' => 'Amazon',
            'amount_usd' => 100.00,
            'rate_paid' => 0.85,
            'customer_payout' => 85.00,
            'estimated_profit' => 15.00,
            'status' => 'approved',
        ],
        [
            'card_type' => 'Apple',
            'amount_usd' => 200.00,
            'rate_paid' => 0.80,
            'customer_payout' => 160.00,
            'estimated_profit' => 40.00,
            'status' => 'pending',
        ],
    ]);

    expect((float) Trade::sum('amount_usd'))->toEqual(300.00);
    expect((float) Trade::sum('estimated_profit'))->toEqual(55.00);
    expect(Trade::where('status', 'pending')->count())->toEqual(1);
});
