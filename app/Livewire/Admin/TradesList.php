<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

class TradesList extends Component
{
    public ?int $userId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->userId = request('user_id') ? (int) request('user_id') : null;
        $this->dateFrom = request('from') ?: null;
        $this->dateTo = request('to') ?: null;
    }

    public function render()
    {
        $customerName = null;
        if ($this->userId) {
            $customerName = User::find($this->userId)?->name ?? 'Customer #'.$this->userId;
        }

        return view('livewire.admin.trades-list', [
            'customerName' => $customerName,
        ])->layout('layouts.admin', ['title' => 'Trades']);
    }
}
