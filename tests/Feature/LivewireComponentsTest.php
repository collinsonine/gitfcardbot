<?php

use App\Livewire\Admin\RatesManager;
use App\Livewire\Admin\TradeApproval;
use App\Models\Rate;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('rates manager can add a new rate', function () {
    Livewire::test(RatesManager::class)
        ->call('toggleForm')
        ->assertSet('showForm', true)
        ->set('cardName', 'Amazon')
        ->set('usdNgn', '1500')
        ->set('gbpNgn', '1900')
        ->set('eurNgn', '1650')
        ->call('save')
        ->assertSet('showForm', false);

    expect(Rate::where('card_name', 'Amazon')->exists())->toBeTrue();
});

it('rates manager can edit an existing rate', function () {
    $rate = Rate::factory()->create(['card_name' => 'Amazon']);

    Livewire::test(RatesManager::class)
        ->call('edit', $rate->id)
        ->assertSet('showForm', true)
        ->assertSet('cardName', 'Amazon')
        ->set('usdNgn', '1550')
        ->call('save');

    expect($rate->fresh()->usd_ngn)->toEqual(1550);
});

it('rates manager can delete a rate', function () {
    $rate = Rate::factory()->create();

    Livewire::test(RatesManager::class)
        ->call('delete', $rate->id);

    expect(Rate::find($rate->id))->toBeNull();
});

it('trade approval can approve a pending trade', function () {
    $user = User::factory()->create(['phone_number' => '+1234567890']);
    $trade = Trade::factory()->for($user)->create(['status' => 'pending']);

    Livewire::test(TradeApproval::class)
        ->call('approve', $trade->id);

    expect($trade->fresh()->status->value)->toBe('approved');
});

it('trade approval can decline a pending trade', function () {
    $user = User::factory()->create(['phone_number' => '+1234567890']);
    $trade = Trade::factory()->for($user)->create(['status' => 'pending']);

    Livewire::test(TradeApproval::class)
        ->call('decline', $trade->id);

    expect($trade->fresh()->status->value)->toBe('declined');
});

it('trade approval only shows pending trades', function () {
    Trade::factory()->create(['status' => 'approved']);
    Trade::factory()->create(['status' => 'declined']);
    Trade::factory()->count(3)->create(['status' => 'pending']);

    $component = Livewire::test(TradeApproval::class);

    expect(count($component->get('pendingTrades')))->toBe(3);
});
