<?php

namespace App\Livewire\Admin;

use App\Models\BotResponse;
use App\StateMachines\ChatStateMachine;
use Livewire\Component;

class BotResponsesManager extends Component
{
    private const array RESPONSE_KEYS = [
        'welcome',
        'card_type_prompt',
        'help_message',
        'invalid_card_type',
        'invalid_amount',
        'invalid_general',
        'three_strikes',
        'new_trade_after_pending',
        'rates_header',
        'no_rates',
    ];

    public array $responses = [];

    public ?int $editingId = null;

    public string $editKey = '';

    public string $editMessage = '';

    public string $editDescription = '';

    public bool $showEditor = false;

    public function mount(): void
    {
        $this->loadResponses();
    }

    public function loadResponses(): void
    {
        $defined = BotResponse::all()->keyBy('key');

        $this->responses = collect(self::RESPONSE_KEYS)->map(function (string $key) use ($defined) {
            $row = $defined->get($key);

            return [
                'id' => $row?->id,
                'key' => $key,
                'message' => $row?->message ?? ChatStateMachine::default($key),
                'description' => $row?->description ?? $this->guessDescription($key),
                'is_custom' => $row !== null,
            ];
        })->values()->toArray();
    }

    public function edit(string $key): void
    {
        $row = BotResponse::firstOrNew(['key' => $key]);
        $this->editingId = $row->id;
        $this->editKey = $key;
        $this->editMessage = $row->message ?? ChatStateMachine::default($key);
        $this->editDescription = $row->description ?? $this->guessDescription($key);
        $this->showEditor = true;
    }

    public function save(): void
    {
        $this->validate([
            'editMessage' => 'required|string',
        ]);

        BotResponse::updateOrCreate(
            ['key' => $this->editKey],
            [
                'message' => $this->editMessage,
                'description' => $this->editDescription ?: null,
            ],
        );

        $this->showEditor = false;
        $this->editingId = null;
        $this->loadResponses();
    }

    public function resetToDefault(string $key): void
    {
        BotResponse::where('key', $key)->delete();
        $this->loadResponses();
    }

    private function guessDescription(string $key): string
    {
        return match ($key) {
            'welcome' => 'Sent when a new user first messages the bot',
            'card_type_prompt' => 'Card type selection menu (1-5)',
            'help_message' => 'Sent when user types "help"',
            'invalid_card_type' => 'When user picks an invalid card type number',
            'invalid_amount' => 'When user enters a non-numeric amount',
            'invalid_general' => 'When bot doesn\'t understand the input',
            'three_strikes' => 'After 3 consecutive invalid inputs — pauses bot',
            'new_trade_after_pending' => 'Trade summary after submission. Use :details and :id as placeholders',
            'rates_header' => 'Header line when user requests rates',
            'no_rates' => 'When no rates are configured',
            default => '',
        };
    }

    public function render()
    {
        return view('livewire.admin.bot-responses-manager')
            ->layout('layouts.admin', ['title' => 'Bot Responses']);
    }
}
