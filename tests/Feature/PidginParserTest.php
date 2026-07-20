<?php

use App\Models\CardAlias;
use App\Models\Rate;
use App\Models\User;
use App\Services\PidginParser;
use App\Services\TradeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('parses a standard trade message', function () {
    Rate::factory()->create(['card_name' => 'Apple', 'usd_ngn' => 0.85, 'gbp_ngn' => 1.08, 'eur_ngn' => 0.94]);

    $parser = app(PidginParser::class);
    $result = $parser->parse('I get apple 100');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBe('Apple');
    expect($result['amount_usd'])->toEqual(100.0);
    expect($result['complete'])->toBeTrue();
});

it('parses pidgin-style messages with slang', function () {
    Rate::factory()->create(['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91]);

    $parser = app(PidginParser::class);

    $result = $parser->parse('how much be steam 500');
    expect($result['card_type'])->toBe('Steam');
    expect($result['amount_usd'])->toEqual(500.0);
    expect($result['complete'])->toBeTrue();

    $result2 = $parser->parse('load razer 200 usd');
    expect($result2['card_type'])->toBeNull();
    expect($result2['amount_usd'])->toEqual(200.0);
    expect($result2['complete'])->toBeFalse();
});

it('handles typos via built-in alias map', function () {
    Rate::factory()->create(['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91]);

    $parser = app(PidginParser::class);
    $result = $parser->parse('I get steeam 100');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBe('Steam');
    expect($result['amount_usd'])->toEqual(100.0);
    expect($result['complete'])->toBeTrue();
});

it('returns null for unparseable messages', function () {
    $parser = app(PidginParser::class);

    expect($parser->parse('hello how are you'))->toBeNull();
    expect($parser->parse('ok'))->toBeNull();
    expect($parser->parse(''))->toBeNull();
});

it('creates a draft trade from a parseable message', function () {
    Rate::factory()->create(['card_name' => 'Amazon', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $draftService = app(TradeDraftService::class);
    $trade = $draftService->parseAndDraft('I get amazon 250', $user);

    expect($trade)->not->toBeNull();
    expect($trade->card_type)->toBe('Amazon');
    expect($trade->amount_usd)->toEqual(250.0);
    expect((float) $trade->rate_paid)->toEqual(0.80);
    expect((float) $trade->customer_payout)->toEqual(200.0);
    expect((float) $trade->estimated_profit)->toEqual(50.0);
    expect($trade->status->value)->toBe('draft');
    expect($trade->source)->toBe('shadow_parser');
    expect($trade->source_message)->toBe('I get amazon 250');
});

it('saves a typo as a new alias when admin corrects a draft', function () {
    Rate::factory()->create(['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $draftService = app(TradeDraftService::class);
    $trade = $draftService->parseAndDraft('I get steeam 100', $user);

    expect($trade)->not->toBeNull();
    expect($trade->card_type)->toBe('Steam');

    $trade->update(['card_type' => 'Steam']);

    $draftService->learnFromCorrection('I get steeam 100', 'Steam');

    $alias = CardAlias::where('alias_word', 'steeam')->first();
    expect($alias)->not->toBeNull();
    expect($alias->resolved_card)->toBe('Steam');
    expect($alias->hit_count)->toBeGreaterThanOrEqual(1);
});

it('uses habit profiling when only amount is given', function () {
    Rate::factory()->create(['card_name' => 'Apple', 'usd_ngn' => 0.85, 'gbp_ngn' => 1.08, 'eur_ngn' => 0.94]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $user->trades()->createMany([
        ['card_type' => 'Apple', 'amount_usd' => 100, 'rate_paid' => 0.85, 'customer_payout' => 85, 'estimated_profit' => 15, 'status' => 'approved'],
        ['card_type' => 'Apple', 'amount_usd' => 200, 'rate_paid' => 0.85, 'customer_payout' => 170, 'estimated_profit' => 30, 'status' => 'approved'],
        ['card_type' => 'Apple', 'amount_usd' => 150, 'rate_paid' => 0.85, 'customer_payout' => 127.50, 'estimated_profit' => 22.50, 'status' => 'approved'],
        ['card_type' => 'Apple', 'amount_usd' => 75, 'rate_paid' => 0.85, 'customer_payout' => 63.75, 'estimated_profit' => 11.25, 'status' => 'approved'],
        ['card_type' => 'Amazon', 'amount_usd' => 50, 'rate_paid' => 0.80, 'customer_payout' => 40, 'estimated_profit' => 10, 'status' => 'approved'],
    ]);

    expect($user->dominantCardType())->toBe('Apple');

    $draftService = app(TradeDraftService::class);
    $trade = $draftService->parseAndDraft('500 ready', $user);

    expect($trade)->not->toBeNull();
    expect($trade->card_type)->toBe('Apple');
    expect($trade->amount_usd)->toEqual(500.0);
});

it('learns from existing card_aliases table', function () {
    Rate::factory()->create(['card_name' => 'eBay', 'usd_ngn' => 0.78, 'gbp_ngn' => 0.99, 'eur_ngn' => 0.87]);

    CardAlias::create([
        'alias_word' => 'ebey',
        'resolved_card' => 'eBay',
        'hit_count' => 5,
    ]);

    $parser = app(PidginParser::class);
    $result = $parser->parse('I wan sell ebey 300');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBe('eBay');
    expect($result['amount_usd'])->toEqual(300.0);
    expect($result['complete'])->toBeTrue();
});

it('confirms a draft and changes status to pending', function () {
    Rate::factory()->create(['card_name' => 'Google Play', 'usd_ngn' => 0.75, 'gbp_ngn' => 0.95, 'eur_ngn' => 0.83]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $draftService = app(TradeDraftService::class);
    $trade = $draftService->parseAndDraft('google play 100', $user);

    expect($trade->status->value)->toBe('draft');

    $confirmed = $draftService->confirmDraft($trade);

    expect($confirmed->status->value)->toBe('pending');
});

it('extracts amounts with USD suffix', function () {
    Rate::factory()->create(['card_name' => 'Amazon', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89]);

    $parser = app(PidginParser::class);
    $result = $parser->parse('apple 50 usd');

    expect($result)->not->toBeNull();
    expect($result['amount_usd'])->toEqual(50.0);
    expect($result['complete'])->toBeTrue();
});

it('rejects amounts over 1 million but still extracts card type', function () {
    $parser = app(PidginParser::class);
    $result = $parser->parse('amazon 9999999');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBe('Amazon');
    expect($result['amount_usd'])->toBeNull();
    expect($result['complete'])->toBeFalse();
});

it('returns incomplete when only card type is given', function () {
    $parser = app(PidginParser::class);
    $result = $parser->parse('apple');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBe('Apple');
    expect($result['amount_usd'])->toBeNull();
    expect($result['complete'])->toBeFalse();
    expect($result['pending_card_type'])->toBe('Apple');
    expect($result['pending_amount'])->toBeNull();
});

it('returns incomplete when only amount is given without habit', function () {
    $parser = app(PidginParser::class);
    $result = $parser->parse('500');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBeNull();
    expect($result['amount_usd'])->toEqual(500.0);
    expect($result['complete'])->toBeFalse();
    expect($result['pending_card_type'])->toBeNull();
    expect($result['pending_amount'])->toEqual(500.0);
});

it('merges card type from first message with amount from second', function () {
    Rate::factory()->create(['card_name' => 'Apple', 'usd_ngn' => 0.85, 'gbp_ngn' => 1.08, 'eur_ngn' => 0.94]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $parser = app(PidginParser::class);
    $draftService = app(TradeDraftService::class);

    // First message: just card type
    $trade = $draftService->parseAndDraft('apple', $user);
    expect($trade)->toBeNull();

    $user->refresh();
    expect($user->pending_card_type)->toBe('Apple');
    expect($user->pending_amount)->toBeNull();

    // Second message: just amount
    $trade = $draftService->parseAndDraft('500', $user);
    expect($trade)->not->toBeNull();
    expect($trade->card_type)->toBe('Apple');
    expect($trade->amount_usd)->toEqual(500.0);
    expect($trade->status->value)->toBe('draft');

    // Pending context should be cleared
    $user->refresh();
    expect($user->pending_card_type)->toBeNull();
    expect($user->pending_amount)->toBeNull();
});

it('merges amount from first message with card type from second', function () {
    Rate::factory()->create(['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91]);
    $user = User::factory()->create(['phone_number' => '+2348012345678']);

    $draftService = app(TradeDraftService::class);

    // First message: just amount
    $trade = $draftService->parseAndDraft('200', $user);
    expect($trade)->toBeNull();

    $user->refresh();
    expect($user->pending_amount)->toEqual(200.0);

    // Second message: just card type
    $trade = $draftService->parseAndDraft('steam', $user);
    expect($trade)->not->toBeNull();
    expect($trade->card_type)->toBe('Steam');
    expect($trade->amount_usd)->toEqual(200.0);
});

it('clears pending context on filler messages', function () {
    $user = User::factory()->create([
        'phone_number' => '+2348012345678',
        'pending_card_type' => 'Apple',
    ]);

    $draftService = app(TradeDraftService::class);
    $trade = $draftService->parseAndDraft('ok', $user);
    expect($trade)->toBeNull();

    $user->refresh();
    expect($user->pending_card_type)->toBeNull();
    expect($user->pending_amount)->toBeNull();
});

it('handles pidgin with amount spelled out as word', function () {
    Rate::factory()->create(['card_name' => 'Amazon', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89]);

    $parser = app(PidginParser::class);
    $result = $parser->parse('I get amazon 100 dollars');

    expect($result)->not->toBeNull();
    expect($result['card_type'])->toBe('Amazon');
    expect($result['amount_usd'])->toEqual(100.0);
    expect($result['complete'])->toBeTrue();
});

it('handles google play variations', function () {
    Rate::factory()->create(['card_name' => 'Google Play', 'usd_ngn' => 0.75, 'gbp_ngn' => 0.95, 'eur_ngn' => 0.83]);

    $parser = app(PidginParser::class);

    $result = $parser->parse('gplay 300');
    expect($result['card_type'])->toBe('Google Play');

    $result2 = $parser->parse('gp 150');
    expect($result2['card_type'])->toBe('Google Play');

    $result3 = $parser->parse('google play 200');
    expect($result3['card_type'])->toBe('Google Play');
});
