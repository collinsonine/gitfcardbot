<?php

use App\Enums\CashFlowType;
use App\Models\CashFlowLog;
use App\Models\Rate;
use App\Models\User;
use App\Services\CashFlowService;
use App\Services\TradeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates cash flow entries when a trade is approved', function () {
    Rate::factory()->create(['card_name' => 'Amazon', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'Amazon',
        'amount_usd' => 200.00,
        'rate_paid' => 0.80,
        'customer_payout' => 160.00,
        'estimated_profit' => 40.00,
        'status' => 'pending',
    ]);

    $cashFlowService = app(CashFlowService::class);
    $cashFlowService->onTradeApproved($trade);

    $cashOut = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::CashOut)
        ->first();

    $revenue = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::Revenue)
        ->first();

    expect($cashOut)->not->toBeNull();
    expect((float) $cashOut->amount)->toEqual(160.00);
    expect($cashOut->description)->toContain("Trade #{$trade->id}");

    expect($revenue)->not->toBeNull();
    expect((float) $revenue->amount)->toEqual(40.00);
    expect($revenue->description)->toContain("Trade #{$trade->id}");
});

it('does not create revenue entry for zero-profit trades', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'Amazon',
        'amount_usd' => 100.00,
        'rate_paid' => 1.00,
        'customer_payout' => 100.00,
        'estimated_profit' => 0.00,
        'status' => 'pending',
    ]);

    $cashFlowService = app(CashFlowService::class);
    $cashFlowService->onTradeApproved($trade);

    $revenue = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::Revenue)
        ->first();

    expect($revenue)->toBeNull();

    $cashOut = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::CashOut)
        ->first();

    expect($cashOut)->not->toBeNull();
    expect((float) $cashOut->amount)->toEqual(100.00);
});

it('correctly aggregates daily cash flow totals', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade1 = $user->trades()->create([
        'card_type' => 'Amazon',
        'amount_usd' => 100.00,
        'rate_paid' => 0.80,
        'customer_payout' => 80.00,
        'estimated_profit' => 20.00,
        'status' => 'pending',
    ]);

    $trade2 = $user->trades()->create([
        'card_type' => 'Steam',
        'amount_usd' => 250.00,
        'rate_paid' => 0.82,
        'customer_payout' => 205.00,
        'estimated_profit' => 45.00,
        'status' => 'pending',
    ]);

    $cashFlowService = app(CashFlowService::class);
    $cashFlowService->onTradeApproved($trade1);
    $cashFlowService->onTradeApproved($trade2);

    expect($cashFlowService->getCashOutToday())->toEqual(285.00);
    expect($cashFlowService->getRevenueToday())->toEqual(65.00);
});

it('calculates available float correctly', function () {
    $cashFlowService = app(CashFlowService::class);

    $cashFlowService->recordCapitalInjection(500000, 'Initial capital');
    $cashFlowService->recordCapitalInjection(200000, 'Second injection');

    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'Apple',
        'amount_usd' => 300.00,
        'rate_paid' => 0.85,
        'customer_payout' => 255.00,
        'estimated_profit' => 45.00,
        'status' => 'pending',
    ]);

    $cashFlowService->onTradeApproved($trade);

    $cashFlowService->recordExpense(5000, 'Network costs');

    $expectedFloat = 700000 - 255 - 5000;

    expect($cashFlowService->getAvailableFloat())->toEqual($expectedFloat);
    expect($cashFlowService->getTotalCapitalIn())->toEqual(700000);
    expect($cashFlowService->getTotalCashOut())->toEqual(255.00);
    expect($cashFlowService->getTotalExpenses())->toEqual(5000.00);
});

it('links cash flow entries to trades via trade_id', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $trade = $user->trades()->create([
        'card_type' => 'eBay',
        'amount_usd' => 500.00,
        'rate_paid' => 0.78,
        'customer_payout' => 390.00,
        'estimated_profit' => 110.00,
        'status' => 'pending',
    ]);

    $cashFlowService = app(CashFlowService::class);
    $cashFlowService->onTradeApproved($trade);

    $entries = CashFlowLog::where('trade_id', $trade->id)->get();

    expect($entries->count())->toBe(2);
    expect($entries->every(fn (CashFlowLog $e) => $e->trade_id === $trade->id))->toBeTrue();
    expect($entries->contains('type', CashFlowType::CashOut))->toBeTrue();
    expect($entries->contains('type', CashFlowType::Revenue))->toBeTrue();
});

it('shadows ledger draft to confirm creates correct cash flow chain', function () {
    Rate::factory()->create(['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $draftService = app(TradeDraftService::class);
    $trade = $draftService->parseAndDraft('steam 500', $user);

    expect($trade->status->value)->toBe('draft');
    expect(CashFlowLog::count())->toBe(0);

    $confirmed = $draftService->confirmDraft($trade);

    expect($confirmed->status->value)->toBe('pending');
    expect(CashFlowLog::where('trade_id', $trade->id)->count())->toBe(0);

    $confirmed->update(['status' => 'approved']);

    $cashFlowService = app(CashFlowService::class);
    $cashFlowService->onTradeApproved($confirmed->fresh());

    $entries = CashFlowLog::where('trade_id', $trade->id)->get();

    expect($entries->count())->toBe(2);

    $cashOut = $entries->firstWhere('type', CashFlowType::CashOut);
    expect((float) $cashOut->amount)->toEqual(round(500 * 0.82, 2));

    $revenue = $entries->firstWhere('type', CashFlowType::Revenue);
    expect((float) $revenue->amount)->toEqual(round(500 - round(500 * 0.82, 2), 2));
});
