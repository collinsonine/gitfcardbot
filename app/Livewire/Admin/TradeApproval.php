<?php

namespace App\Livewire\Admin;

use App\Enums\TradeStatus;
use App\Models\Trade;
use Livewire\Component;

class TradeApproval extends Component
{
    public array $pendingTrades = [];

    public function mount(): void
    {
        $this->loadPending();
    }

    public function loadPending(): void
    {
        $this->pendingTrades = Trade::with('user')
            ->where('status', TradeStatus::Pending)
            ->latest()
            ->get()
            ->toArray();
    }

    public function approve(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $trade->update(['status' => TradeStatus::Approved]);

        $this->loadPending();
    }

    public function decline(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $trade->update(['status' => TradeStatus::Declined]);

        $this->loadPending();
    }

    public function render()
    {
        return view('livewire.admin.trade-approval')
            ->layout('layouts.admin', ['title' => 'Trade Approval']);
    }
}
