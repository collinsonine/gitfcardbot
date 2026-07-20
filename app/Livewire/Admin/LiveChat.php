<?php

namespace App\Livewire\Admin;

use App\Enums\ChatDirection;
use App\Models\ChatLog;
use App\Models\User;
use App\Services\WhatsAppService;
use Livewire\Component;

class LiveChat extends Component
{
    public User $customer;

    public string $newMessage = '';

    public array $messages = [];

    protected WhatsAppService $whatsApp;

    public function boot(WhatsAppService $whatsApp): void
    {
        $this->whatsApp = $whatsApp;
    }

    public function mount(int $userId): void
    {
        $this->customer = User::findOrFail($userId);
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        $this->messages = ChatLog::where('user_id', $this->customer->id)
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    public function toggleBotPause(): void
    {
        $this->customer->is_bot_paused = ! $this->customer->is_bot_paused;
        $this->customer->save();
    }

    public function sendMessage(): void
    {
        $this->validate(['newMessage' => 'required|string|max:4096']);

        $message = trim($this->newMessage);

        ChatLog::create([
            'user_id' => $this->customer->id,
            'direction' => ChatDirection::Outbound,
            'message_body' => $message,
        ]);

        $this->whatsApp->sendMessage($this->customer->whatsapp_id ?? $this->customer->phone_number, $message);

        $this->newMessage = '';
        $this->loadMessages();
    }

    public function render()
    {
        return view('livewire.admin.live-chat')
            ->layout('layouts.admin', ['title' => 'Chat']);
    }
}
