<?php

use App\Enums\CashFlowType;
use App\Enums\TradeStatus;
use App\Livewire\Admin\TransactionTable;
use App\Models\CashFlowLog;
use App\Models\Rate;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the transaction table in ledger context', function () {
    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->assertStatus(200);
});

it('renders the transaction table in trades context', function () {
    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertStatus(200);
});

it('shows cash flow entries in ledger context', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    $trade = Trade::factory()->for($user)->create([
        'status' => TradeStatus::Completed,
        'source' => 'manual',
    ]);

    CashFlowLog::create([
        'trade_id' => $trade->id,
        'type' => CashFlowType::CashOut,
        'amount' => $trade->customer_payout,
        'description' => "Trade #{$trade->id} approved",
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->assertSee("Trade #{$trade->id} approved")
        ->assertSee($user->name);
});

it('shows trades in trades context', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Amazon']);
    Trade::factory()->for($user)->create([
        'card_type' => 'Amazon',
        'status' => TradeStatus::Pending,
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertSee('Amazon')
        ->assertSee('Pending');
});

it('filters by status in trades context', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    $pending = Trade::factory()->for($user)->create(['status' => TradeStatus::Pending, 'card_type' => 'Amazon']);
    $approved = Trade::factory()->for($user)->create(['status' => TradeStatus::Approved, 'card_type' => 'Steam']);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertSee('Amazon')
        ->assertSee('Steam')
        ->set('statusFilter', 'approved')
        ->assertDontSee('Amazon')
        ->assertSee('Steam');
});

it('filters by type in ledger context', function () {
    $trade = Trade::factory()->create(['status' => TradeStatus::Completed]);

    CashFlowLog::create([
        'trade_id' => $trade->id,
        'type' => CashFlowType::CashOut,
        'amount' => 100,
        'description' => 'Cash out entry',
    ]);

    CashFlowLog::create([
        'trade_id' => null,
        'type' => CashFlowType::CapitalInjection,
        'amount' => 50000,
        'description' => 'Capital injection',
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->assertSee('Cash out entry')
        ->assertSee('Capital injection')
        ->set('typeFilter', 'capital_injection')
        ->assertDontSee('Cash out entry')
        ->assertSee('Capital injection');
});

it('filters by date range', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    Trade::factory()->for($user)->create([
        'created_at' => now()->subDays(5),
        'status' => TradeStatus::Completed,
    ]);

    Trade::factory()->for($user)->create([
        'created_at' => now(),
        'status' => TradeStatus::Completed,
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->set('dateFrom', now()->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('1');
});

it('searches by customer name', function () {
    $user = User::factory()->create(['name' => 'John Smith', 'phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Amazon']);
    Trade::factory()->for($user)->create(['card_type' => 'Amazon', 'status' => TradeStatus::Pending]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertSee('John Smith')
        ->set('search', 'John')
        ->assertSee('John Smith');
});

it('searches by card type', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Steam']);
    Trade::factory()->for($user)->create(['card_type' => 'Steam', 'status' => TradeStatus::Pending]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->set('search', 'Steam')
        ->assertSee('Steam');
});

it('clears all filters', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Trade::factory()->for($user)->create(['status' => TradeStatus::Pending]);
    Trade::factory()->for($user)->create(['status' => TradeStatus::Approved]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->set('statusFilter', 'approved')
        ->call('clearFilters')
        ->assertSet('statusFilter', '')
        ->assertSet('search', '')
        ->assertSet('dateFrom', null)
        ->assertSet('dateTo', null);
});

it('sets quick period correctly', function () {
    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->call('setQuickPeriod', 'today')
        ->assertSet('dateFrom', now()->toDateString())
        ->assertSet('dateTo', now()->toDateString());

    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->call('setQuickPeriod', 'all')
        ->assertSet('dateFrom', null)
        ->assertSet('dateTo', null);
});

it('sorts by date', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    Trade::factory()->for($user)->create(['created_at' => now()->subDays(1), 'status' => TradeStatus::Completed]);
    Trade::factory()->for($user)->create(['created_at' => now(), 'status' => TradeStatus::Completed]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertSet('sortField', 'created_at')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'created_at')
        ->assertSet('sortDirection', 'asc');
});

it('filters by user id', function () {
    $user1 = User::factory()->create(['phone_number' => '+2348012345678']);
    $user2 = User::factory()->create(['phone_number' => '+2348098765432']);
    Trade::factory()->for($user1)->create(['status' => TradeStatus::Completed]);
    Trade::factory()->for($user2)->create(['status' => TradeStatus::Completed]);

    Livewire::test(TransactionTable::class, ['context' => 'trades', 'userId' => $user1->id])
        ->assertSee($user1->name)
        ->assertDontSee($user2->name);
});

it('computes ledger aggregates correctly', function () {
    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->assertSet('aggregates.total_revenue', 0.0)
        ->assertSet('aggregates.total_cash_out', 0.0)
        ->assertSet('aggregates.total_capital', 0.0)
        ->assertSet('aggregates.total_expenses', 0.0);
});

it('computes trade aggregates correctly', function () {
    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertSet('aggregates.total_volume', 0.0)
        ->assertSet('aggregates.total_profit', 0.0);
});

it('exports csv successfully', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Amazon']);
    Trade::factory()->for($user)->create([
        'card_type' => 'Amazon',
        'status' => TradeStatus::Completed,
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->call('exportCsv')
        ->assertStatus(200);
});

it('exports pdf without errors', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Amazon']);
    Trade::factory()->for($user)->create([
        'card_type' => 'Amazon',
        'status' => TradeStatus::Completed,
    ]);

    $component = new TransactionTable();
    $component->context = 'trades';
    $response = $component->exportPdf();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('accepts initial date range from parent', function () {
    Livewire::test(TransactionTable::class, [
        'context' => 'trades',
        'dateFrom' => '2026-01-01',
        'dateTo' => '2026-01-31',
    ])
        ->assertSet('dateFrom', '2026-01-01')
        ->assertSet('dateTo', '2026-01-31');
});

it('refreshes table on event', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Trade::factory()->for($user)->create(['status' => TradeStatus::Completed]);

    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->call('refreshTable')
        ->assertSet('search', '');
});

it('deletes a trade and its cash flow logs from trades context', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Amazon']);
    $trade = Trade::factory()->for($user)->create([
        'card_type' => 'Amazon',
        'status' => TradeStatus::Completed,
    ]);

    CashFlowLog::create([
        'trade_id' => $trade->id,
        'type' => CashFlowType::CashOut,
        'amount' => 425,
        'description' => 'Payout',
    ]);

    CashFlowLog::create([
        'trade_id' => $trade->id,
        'type' => CashFlowType::Revenue,
        'amount' => 75,
        'description' => 'Profit',
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'trades'])
        ->assertSee('Amazon')
        ->call('deleteTrade', $trade->id)
        ->assertDontSee('Amazon');

    expect(Trade::find($trade->id))->toBeNull();
    expect(CashFlowLog::where('trade_id', $trade->id)->count())->toBe(0);
});

it('deletes a trade-linked entry and its trade from ledger context', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);
    Rate::factory()->create(['card_name' => 'Steam']);
    $trade = Trade::factory()->for($user)->create([
        'card_type' => 'Steam',
        'status' => TradeStatus::Completed,
    ]);

    $entry = CashFlowLog::create([
        'trade_id' => $trade->id,
        'type' => CashFlowType::CashOut,
        'amount' => 410,
        'description' => 'Steam payout',
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->assertSee('Steam payout')
        ->call('deleteEntry', $entry->id)
        ->assertDontSee('Steam payout');

    expect(Trade::find($trade->id))->toBeNull();
    expect(CashFlowLog::where('trade_id', $trade->id)->count())->toBe(0);
});

it('deletes a standalone cash flow entry from ledger context', function () {
    $entry = CashFlowLog::create([
        'trade_id' => null,
        'type' => CashFlowType::CapitalInjection,
        'amount' => 100000,
        'description' => 'Seed capital',
    ]);

    Livewire::test(TransactionTable::class, ['context' => 'ledger'])
        ->assertSee('Seed capital')
        ->call('deleteEntry', $entry->id)
        ->assertDontSee('Seed capital');

    expect(CashFlowLog::find($entry->id))->toBeNull();
});
