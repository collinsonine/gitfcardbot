<?php

namespace App\Livewire\Admin;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public array $totals = [];

    public array $recentTrades = [];

    public array $customerLifespans = [];

    public int $draftCount = 0;

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->totals = [
            'volume' => Trade::sum('amount_usd'),
            'profit' => Trade::sum('estimated_profit'),
            'pending_count' => Trade::where('status', TradeStatus::Pending)->count(),
            'draft_count' => Trade::where('status', TradeStatus::Draft)->count(),
            'total_trades' => Trade::count(),
        ];

        $this->draftCount = $this->totals['draft_count'];

        $this->recentTrades = Trade::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->toArray();

        $this->customerLifespans = User::whereNotNull('phone_number')
            ->withCount('trades')
            ->withSum('trades', 'amount_usd')
            ->withSum('trades', 'estimated_profit')
            ->latest()
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone_number,
                'lifetime' => $u->created_at->diffForHumans(),
                'trades_count' => $u->trades_count,
                'total_volume' => $u->trades_sum_amount_usd ?? 0,
                'total_profit' => $u->trades_sum_estimated_profit ?? 0,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.admin', ['title' => 'Dashboard']);
    }
}
