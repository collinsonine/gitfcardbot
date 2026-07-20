<?php

use App\Models\ChatLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('logs inbound message without sending a reply', function () {
    $user = User::factory()->create([
        'phone_number' => '+1234567890',
    ]);

    Http::fake();

    postJson('/api/whatsapp/webhook', [
        'phone' => $user->phone_number,
        'message' => 'I want to sell apple $500',
    ])->assertOk()->assertJson(['handled' => true]);

    expect(ChatLog::where('user_id', $user->id)->where('direction', 'inbound')->count())->toBe(1);
    expect(ChatLog::where('user_id', $user->id)->where('direction', 'outbound')->count())->toBe(0);
});

it('creates a new user on first contact', function () {
    Http::fake();

    postJson('/api/whatsapp/webhook', [
        'phone' => '+9998887777',
        'message' => 'hello',
        'name' => 'New User',
    ])->assertOk();

    expect(User::where('phone_number', '+9998887777')->exists())->toBeTrue();
});

it('logs media messages', function () {
    $user = User::factory()->create(['phone_number' => '+1234567890']);

    Http::fake();

    postJson('/api/whatsapp/webhook', [
        'phone' => $user->phone_number,
        'message' => '',
        'has_media' => true,
        'media_path' => '/tmp/test.jpg',
    ])->assertOk();

    $log = ChatLog::where('user_id', $user->id)->first();
    expect($log)->not->toBeNull();
    expect($log->has_media)->toBeTrue();
});

it('validates required phone field', function () {
    postJson('/api/whatsapp/webhook', [
        'message' => 'hello',
    ])->assertUnprocessable();
});
