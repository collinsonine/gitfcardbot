<?php

use App\Enums\CashFlowType;
use App\Livewire\Admin\Ledger;
use App\Models\CashFlowLog;
use App\Models\Rate;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the ledger page', function () {
    Livewire::test(Ledger::class)
        ->assertStatus(200);
});

it('opens modal when openModal is called', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->assertSet('editingEntryId', null)
        ->assertSet('entryType', 'manual_trade');
});

it('can create a manual trade with correct profit calculation', function () {
    Rate::factory()->create(['card_name' => 'Amazon']);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'manual_trade')
        ->set('cardType', 'Amazon')
        ->set('amountUsd', '200')
        ->set('ratePaid', '0.80')
        ->set('customerId', $user->id)
        ->call('saveEntry')
        ->assertSet('showModal', false);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->card_type)->toBe('Amazon');
    expect((float) $trade->amount_usd)->toEqual(200.00);
    expect((float) $trade->rate_paid)->toEqual(0.80);
    expect((float) $trade->customer_payout)->toEqual(160.00);
    expect((float) $trade->estimated_profit)->toEqual(40.00);
    expect($trade->user_id)->toBe($user->id);
    expect($trade->status->value)->toBe('completed');
    expect($trade->source)->toBe('manual');

    $cashOut = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::CashOut)
        ->first();
    expect($cashOut)->not->toBeNull();
    expect((float) $cashOut->amount)->toEqual(160.00);

    $revenue = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::Revenue)
        ->first();
    expect($revenue)->not->toBeNull();
    expect((float) $revenue->amount)->toEqual(40.00);
});

it('skips revenue entry for zero-profit manual trade', function () {
    Rate::factory()->create(['card_name' => 'Apple']);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'manual_trade')
        ->set('cardType', 'Apple')
        ->set('amountUsd', '100')
        ->set('ratePaid', '1.00')
        ->set('customerId', $user->id)
        ->call('saveEntry');

    $trade = Trade::first();
    expect((float) $trade->estimated_profit)->toEqual(0);

    $revenue = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::Revenue)
        ->first();
    expect($revenue)->toBeNull();

    $cashOut = CashFlowLog::where('trade_id', $trade->id)
        ->where('type', CashFlowType::CashOut)
        ->first();
    expect($cashOut)->not->toBeNull();
});

it('can create a capital injection entry', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'capital_injection')
        ->set('amountUsd', '500000')
        ->set('entryDescription', 'Initial capital')
        ->call('saveEntry')
        ->assertSet('showModal', false);

    $entry = CashFlowLog::where('type', CashFlowType::CapitalInjection)->first();
    expect($entry)->not->toBeNull();
    expect((float) $entry->amount)->toEqual(500000);
    expect($entry->description)->toBe('Initial capital');
    expect($entry->trade_id)->toBeNull();
});

it('can create an expense entry', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'expense')
        ->set('amountUsd', '5000')
        ->set('entryDescription', 'Network costs')
        ->call('saveEntry')
        ->assertSet('showModal', false);

    $entry = CashFlowLog::where('type', CashFlowType::Expense)->first();
    expect($entry)->not->toBeNull();
    expect((float) $entry->amount)->toEqual(5000);
    expect($entry->description)->toBe('Network costs');
});

it('can search for customers', function () {
    User::factory()->create(['name' => 'John Doe', 'phone_number' => '+2348012345678']);
    User::factory()->create(['name' => 'Jane Smith', 'phone_number' => '+2348098765432']);

    Livewire::test(Ledger::class)
        ->set('customerSearch', 'John')
        ->assertSet('customerResults', [
            ['id' => 1, 'name' => 'John Doe', 'phone' => '+2348012345678'],
        ]);
});

it('can select a customer from search results', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'phone_number' => '+2348012345678']);

    Livewire::test(Ledger::class)
        ->set('customerSearch', 'John')
        ->call('selectCustomer', $user->id)
        ->assertSet('customerId', $user->id)
        ->assertSet('customerSearch', 'John Doe')
        ->assertSet('customerResults', []);
});

it('can clear selected customer', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    Livewire::test(Ledger::class)
        ->call('selectCustomer', $user->id)
        ->assertSet('customerId', $user->id)
        ->call('clearCustomer')
        ->assertSet('customerId', null)
        ->assertSet('customerSearch', '');
});

it('validates required fields for manual trade', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'manual_trade')
        ->set('cardType', '')
        ->set('amountUsd', '')
        ->set('ratePaid', '')
        ->call('saveEntry')
        ->assertHasErrors(['cardType', 'amountUsd', 'ratePaid', 'customerId']);
});

it('validates entry type is limited to allowed types', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'cash_out')
        ->set('amountUsd', '100')
        ->call('saveEntry')
        ->assertHasErrors(['entryType']);
});

it('validates amount must be positive', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'capital_injection')
        ->set('amountUsd', '0')
        ->call('saveEntry')
        ->assertHasErrors(['amountUsd']);
});

it('resets form when modal is closed', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'expense')
        ->set('amountUsd', '5000')
        ->set('entryDescription', 'Some cost')
        ->call('openModal')
        ->assertSet('showModal', true)
        ->assertSet('entryType', 'manual_trade')
        ->assertSet('amountUsd', '')
        ->assertSet('entryDescription', '')
        ->assertSet('editingEntryId', null);
});

it('refreshes stats after creating an entry', function () {
    Livewire::test(Ledger::class)
        ->assertSet('totalCapital', 0.0);

    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'capital_injection')
        ->set('amountUsd', '100000')
        ->set('entryDescription', 'Capital added')
        ->call('saveEntry');

    Livewire::test(Ledger::class)
        ->assertSet('totalCapital', 100000.0);
});

it('can delete a cash flow entry', function () {
    $entry = CashFlowLog::create([
        'trade_id' => null,
        'type' => CashFlowType::Expense,
        'amount' => 1000,
        'description' => 'To be deleted',
    ]);

    Livewire::test(Ledger::class)
        ->call('deleteEntry', $entry->id);

    expect(CashFlowLog::find($entry->id))->toBeNull();
});

it('deletes trade and all cash flow entries when deleting a trade-linked entry', function () {
    Rate::factory()->create(['card_name' => 'Steam']);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'manual_trade')
        ->set('cardType', 'Steam')
        ->set('amountUsd', '500')
        ->set('ratePaid', '0.82')
        ->set('customerId', $user->id)
        ->call('saveEntry');

    $trade = Trade::first();
    expect(CashFlowLog::where('trade_id', $trade->id)->count())->toBe(2);

    Livewire::test(Ledger::class)
        ->call('deleteEntry', CashFlowLog::where('trade_id', $trade->id)->first()->id);

    expect(Trade::find($trade->id))->toBeNull();
    expect(CashFlowLog::where('trade_id', $trade->id)->count())->toBe(0);
});

it('computes correct payout and profit', function () {
    Livewire::test(Ledger::class)
        ->set('amountUsd', '250')
        ->set('ratePaid', '0.85')
        ->assertSet('computedPayout', '212.50')
        ->assertSet('computedProfit', '37.50');
});

it('computes zero when amounts not set', function () {
    Livewire::test(Ledger::class)
        ->assertSet('computedPayout', '0.00')
        ->assertSet('computedProfit', '0.00');
});

it('clears manual trade fields when switching to expense type', function () {
    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'manual_trade')
        ->set('cardType', 'Amazon')
        ->set('amountUsd', '100')
        ->set('ratePaid', '0.80')
        ->set('entryType', 'expense')
        ->assertSet('cardType', '')
        ->assertSet('ratePaid', '')
        ->assertSet('customerId', null)
        ->assertSet('computedPayout', '0.00')
        ->assertSet('computedProfit', '0.00');
});

it('displays entries in the cash flow table', function () {
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    Livewire::test(Ledger::class)
        ->call('openModal')
        ->set('entryType', 'capital_injection')
        ->set('amountUsd', '100000')
        ->set('entryDescription', 'Test capital')
        ->call('saveEntry');

    Livewire::test(Ledger::class)
        ->assertSee('Test capital')
        ->assertSee('100,000.00');
});
