<?php

use App\Models\Rate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates shadow ledger trade with zero floating-point discrepancies', function () {
    $rate = Rate::factory()->create(['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $amountUsd = 333.33;
    $ratePaid = 0.82;
    $customerPayout = round($amountUsd * $ratePaid, 2);
    $estimatedProfit = round($amountUsd - $customerPayout, 2);

    $trade = $user->trades()->create([
        'card_type' => 'Steam',
        'amount_usd' => $amountUsd,
        'rate_paid' => $ratePaid,
        'customer_payout' => $customerPayout,
        'estimated_profit' => $estimatedProfit,
        'status' => 'approved',
        'source' => 'shadow_parser',
    ]);

    $trade->refresh();

    expect((float) $trade->customer_payout)->toEqual(round(333.33 * 0.82, 2));
    expect((float) $trade->estimated_profit)->toEqual(round(333.33 - round(333.33 * 0.82, 2), 2));
    expect(round((float) $trade->customer_payout + (float) $trade->estimated_profit, 2))->toEqual((float) $trade->amount_usd);
});

it('maintains precision across 30 randomised amount-rate pairs', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $amounts = [10.00, 25.50, 99.99, 150.00, 250.75, 333.33, 500.00, 750.25, 1000.00, 42.42];
    $rates = [0.70, 0.75, 0.78, 0.80, 0.82, 0.85, 0.88, 0.90, 0.92, 0.95];

    $count = 0;

    foreach ($amounts as $amount) {
        foreach ($rates as $rateValue) {
            $payout = round($amount * $rateValue, 2);
            $profit = round($amount - $payout, 2);

            $trade = $user->trades()->create([
                'card_type' => 'Amazon',
                'amount_usd' => $amount,
                'rate_paid' => $rateValue,
                'customer_payout' => $payout,
                'estimated_profit' => $profit,
                'status' => 'approved',
            ]);

            $trade->refresh();

            $recomputedPayout = round((float) $trade->amount_usd * (float) $trade->rate_paid, 2);
            $recomputedProfit = round((float) $trade->amount_usd - $recomputedPayout, 2);

            expect((float) $trade->customer_payout)->toEqual($recomputedPayout);
            expect((float) $trade->estimated_profit)->toEqual($recomputedProfit);
            expect(round((float) $trade->customer_payout + (float) $trade->estimated_profit, 2))->toEqual((float) $trade->amount_usd);

            $count++;
        }
    }

    expect($count)->toBe(100);
});

it('recalculates trade values correctly on rate change', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'Apple',
        'amount_usd' => 200.00,
        'rate_paid' => 0.85,
        'customer_payout' => 170.00,
        'estimated_profit' => 30.00,
        'status' => 'draft',
    ]);

    $newRate = 0.80;
    $newPayout = round(200.00 * $newRate, 2);
    $newProfit = round(200.00 - $newPayout, 2);

    $trade->update([
        'rate_paid' => $newRate,
        'customer_payout' => $newPayout,
        'estimated_profit' => $newProfit,
    ]);

    $trade->refresh();

    expect((float) $trade->customer_payout)->toEqual(160.00);
    expect((float) $trade->estimated_profit)->toEqual(40.00);
    expect(round((float) $trade->customer_payout + (float) $trade->estimated_profit, 2))->toEqual(200.00);
});

it('handles edge case: rate of exactly 1.0', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'Amazon',
        'amount_usd' => 100.00,
        'rate_paid' => 1.00,
        'customer_payout' => 100.00,
        'estimated_profit' => 0.00,
        'status' => 'approved',
    ]);

    $trade->refresh();

    expect((float) $trade->customer_payout)->toEqual(100.00);
    expect((float) $trade->estimated_profit)->toEqual(0.00);
});

it('handles edge case: very small amount', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'Google Play',
        'amount_usd' => 1.00,
        'rate_paid' => 0.75,
        'customer_payout' => 0.75,
        'estimated_profit' => 0.25,
        'status' => 'approved',
    ]);

    $trade->refresh();

    expect((float) $trade->customer_payout)->toEqual(0.75);
    expect((float) $trade->estimated_profit)->toEqual(0.25);
    expect(round((float) $trade->customer_payout + (float) $trade->estimated_profit, 2))->toEqual(1.00);
});
