<?php

namespace App\Livewire\Admin;

use App\Enums\CashFlowType;
use App\Enums\TradeStatus;
use App\Models\CashFlowLog;
use App\Models\Rate;
use App\Models\Trade;
use App\Models\User;
use App\Services\CashFlowService;
use Livewire\Attributes\On;
use Livewire\Component;

class Ledger extends Component
{
    public float $todayPayout = 0;

    public float $todayProfit = 0;

    public float $availableFloat = 0;

    public float $totalRevenue = 0;

    public float $totalCashOut = 0;

    public float $totalCapital = 0;

    public array $userDirectory = [];

    public bool $showModal = false;

    public ?int $editingEntryId = null;

    public string $entryType = 'manual_trade';

    public string $cardType = '';

    public string $amountUsd = '';

    public string $ratePaid = '';

    public ?int $customerId = null;

    public string $customerSearch = '';

    public array $customerResults = [];

    public bool $showNewCustomer = false;

    public string $newCustomerName = '';

    public string $newCustomerPhone = '';

    public string $entryDescription = '';

    public string $computedPayout = '0.00';

    public string $computedProfit = '0.00';

    public array $cardTypes = [];

    public ?string $selectedCurrency = 'usd';

    protected CashFlowService $cashFlowService;

    public function boot(): void
    {
        $this->cashFlowService = app(CashFlowService::class);
    }

    public function mount(): void
    {
        $this->loadCardTypes();
        $this->refreshStats();
    }

    public function loadCardTypes(): void
    {
        $this->cardTypes = Rate::pluck('card_name')->toArray();
    }

    public function updatedEntryType(): void
    {
        if ($this->entryType !== 'manual_trade') {
            $this->cardType = '';
            $this->ratePaid = '';
            $this->customerId = null;
            $this->customerSearch = '';
            $this->customerResults = [];
            $this->showNewCustomer = false;
            $this->newCustomerName = '';
            $this->newCustomerPhone = '';
        }

        $this->recalculateComputed();
    }

    public function updatedCardType(): void
    {
        $this->autoFillRateFromCardType();
        $this->recalculateComputed();
    }

    public function updatedAmountUsd(): void
    {
        $this->recalculateComputed();
    }

    public function updatedRatePaid(): void
    {
        $this->recalculateComputed();
    }

    public function updatedSelectedCurrency(): void
    {
        $this->autoFillRateFromCardType();
        $this->recalculateComputed();
    }

    private function autoFillRateFromCardType(): void
    {
        if (! $this->cardType) {
            return;
        }

        $rate = Rate::where('card_name', $this->cardType)->first();

        if (! $rate) {
            return;
        }

        $this->ratePaid = (string) match ($this->selectedCurrency) {
            'gbp' => $rate->gbp_ngn,
            'eur' => $rate->eur_ngn,
            default => $rate->usd_ngn,
        };
    }

    private function recalculateComputed(): void
    {
        $amount = (float) $this->amountUsd;
        $rate = (float) $this->ratePaid;

        if ($amount <= 0 || $rate <= 0 || $this->entryType !== 'manual_trade') {
            $this->computedPayout = '0.00';
            $this->computedProfit = '0.00';

            return;
        }

        $payout = round($amount * $rate, 2);
        $profit = round($amount - $payout, 2);

        $this->computedPayout = number_format($payout, 2, '.', '');
        $this->computedProfit = number_format(max(0, $profit), 2, '.', '');
    }

    public function updatedCustomerSearch(): void
    {
        $search = trim($this->customerSearch);

        if (strlen($search) < 2) {
            $this->customerResults = [];
            $this->customerId = null;

            return;
        }

        $this->customerResults = User::where('name', 'like', "%{$search}%")
            ->orWhere('phone_number', 'like', "%{$search}%")
            ->limit(10)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone_number ?? 'No phone',
            ])
            ->toArray();
    }

    public function selectCustomer(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->customerId = $user->id;
        $this->customerSearch = $user->name;
        $this->customerResults = [];
        $this->showNewCustomer = false;
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerSearch = '';
        $this->customerResults = [];
    }

    public function toggleNewCustomer(): void
    {
        $this->showNewCustomer = ! $this->showNewCustomer;

        if ($this->showNewCustomer) {
            $this->customerId = null;
            $this->customerSearch = '';
            $this->customerResults = [];
        } else {
            $this->newCustomerName = '';
            $this->newCustomerPhone = '';
        }
    }

    public function refreshStats(): void
    {
        $this->todayPayout = $this->cashFlowService->getCashOutToday();
        $this->todayProfit = $this->cashFlowService->getRevenueToday();
        $this->availableFloat = $this->cashFlowService->getAvailableFloat();
        $this->totalRevenue = $this->cashFlowService->getTotalRevenue();
        $this->totalCashOut = $this->cashFlowService->getTotalCashOut();
        $this->totalCapital = $this->cashFlowService->getTotalCapitalIn();
        $this->loadUserDirectory();
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->recalculateComputed();
        $this->showModal = true;
    }

    public function editEntry(int $entryId): void
    {
        $entry = CashFlowLog::findOrFail($entryId);
        $this->editingEntryId = $entry->id;
        $this->entryType = $entry->type->value === 'cash_out' || $entry->type->value === 'revenue'
            ? 'manual_trade'
            : $entry->type->value;

        if ($entry->trade_id) {
            $trade = $entry->trade;
            $this->cardType = $trade->card_type ?? '';
            $this->amountUsd = (string) $trade->amount_usd;
            $this->ratePaid = (string) $trade->rate_paid;
            $this->customerId = $trade->user_id;
            $this->customerSearch = $trade->user?->name ?? '';
        } else {
            $this->amountUsd = (string) $entry->amount;
        }

        $this->entryDescription = $entry->description ?? '';
        $this->recalculateComputed();
        $this->showModal = true;
    }

    public function saveEntry(): void
    {
        if ($this->entryType === 'manual_trade') {
            $this->saveManualTrade();
        } else {
            $this->saveCashFlowEntry();
        }
    }

    private function saveManualTrade(): void
    {
        $validated = $this->validate([
            'cardType' => 'required|string|exists:rates,card_name',
            'amountUsd' => 'required|numeric|min:0.01|max:99999999.99',
            'ratePaid' => 'required|numeric|min:0.01|max:99999.99',
            'customerId' => 'required|integer|exists:users,id',
            'entryDescription' => 'nullable|string|max:500',
        ]);

        $amount = (float) $validated['amountUsd'];
        $rate = (float) $validated['ratePaid'];
        $payout = round($amount * $rate, 2);
        $profit = round($amount - $payout, 2);

        if ($this->editingEntryId) {
            $entry = CashFlowLog::findOrFail($this->editingEntryId);
            $trade = $entry->trade;

            if ($trade) {
                $trade->update([
                    'card_type' => $validated['cardType'],
                    'amount_usd' => $amount,
                    'rate_paid' => $rate,
                    'customer_payout' => $payout,
                    'estimated_profit' => $profit,
                    'user_id' => $validated['customerId'],
                ]);

                $entry->update([
                    'amount' => $payout,
                    'description' => $validated['entryDescription'] ?: "Trade #{$trade->id} - {$validated['cardType']} \${$amount}",
                ]);

                $revenueEntry = CashFlowLog::where('trade_id', $trade->id)
                    ->where('type', CashFlowType::Revenue)
                    ->first();

                if ($revenueEntry && $profit > 0) {
                    $revenueEntry->update(['amount' => $profit]);
                } elseif ($revenueEntry && $profit <= 0) {
                    $revenueEntry->delete();
                } elseif (! $revenueEntry && $profit > 0) {
                    CashFlowLog::create([
                        'trade_id' => $trade->id,
                        'type' => CashFlowType::Revenue,
                        'amount' => $profit,
                        'description' => "Profit from Trade #{$trade->id} - {$validated['cardType']}",
                    ]);
                }
            }
        } else {
            $trade = Trade::create([
                'user_id' => $validated['customerId'],
                'card_type' => $validated['cardType'],
                'amount_usd' => $amount,
                'rate_paid' => $rate,
                'customer_payout' => $payout,
                'estimated_profit' => $profit,
                'status' => TradeStatus::Completed,
                'source' => 'manual',
                'admin_notes' => $validated['entryDescription'] ?: null,
                'paid_at' => now(),
            ]);

            $this->cashFlowService->onTradeApproved($trade);
        }

        $this->resetForm();
        $this->refreshStats();
        $this->dispatch('refresh-transactions');
    }

    private function saveCashFlowEntry(): void
    {
        $this->validate([
            'entryType' => 'required|in:capital_injection,expense',
            'amountUsd' => 'required|numeric|min:0.01|max:99999999999.99',
            'entryDescription' => 'nullable|string|max:500',
        ]);

        if ($this->editingEntryId) {
            $entry = CashFlowLog::findOrFail($this->editingEntryId);
            $entry->update([
                'type' => $this->entryType,
                'amount' => $this->amountUsd,
                'description' => $this->entryDescription ?: null,
            ]);
        } else {
            CashFlowLog::create([
                'trade_id' => null,
                'type' => $this->entryType,
                'amount' => $this->amountUsd,
                'description' => $this->entryDescription ?: null,
            ]);
        }

        $this->resetForm();
        $this->refreshStats();
        $this->dispatch('refresh-transactions');
    }

    public function deleteEntry(int $entryId): void
    {
        $entry = CashFlowLog::findOrFail($entryId);

        if ($entry->trade_id) {
            $trade = $entry->trade;
            CashFlowLog::where('trade_id', $trade->id)->delete();
            $trade->delete();
        } else {
            $entry->delete();
        }

        $this->refreshStats();
        $this->dispatch('refresh-transactions');
    }

    private function resetForm(): void
    {
        $this->editingEntryId = null;
        $this->entryType = 'manual_trade';
        $this->cardType = '';
        $this->amountUsd = '';
        $this->ratePaid = '';
        $this->selectedCurrency = 'usd';
        $this->customerId = null;
        $this->customerSearch = '';
        $this->customerResults = [];
        $this->showNewCustomer = false;
        $this->newCustomerName = '';
        $this->newCustomerPhone = '';
        $this->entryDescription = '';
        $this->computedPayout = '0.00';
        $this->computedProfit = '0.00';
        $this->showModal = false;
    }

    public function loadUserDirectory(): void
    {
        $this->userDirectory = User::whereNotNull('phone_number')
            ->withCount(['trades as completed_trades' => fn ($q) => $q->where('status', 'completed')])
            ->withCount('trades')
            ->withSum('trades', 'amount_usd')
            ->withSum('trades', 'estimated_profit')
            ->latest()
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone_number,
                'lifespan' => $u->created_at->diffForHumans(),
                'trades_count' => $u->trades_count,
                'total_volume' => (float) ($u->trades_sum_amount_usd ?? 0),
                'total_profit' => (float) ($u->trades_sum_estimated_profit ?? 0),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.ledger')
            ->layout('layouts.admin', ['title' => 'Accounting Ledger']);
    }
}
