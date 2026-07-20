<?php

namespace App\Livewire\Admin;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use App\Services\CashFlowService;
use App\Services\TradeDraftService;
use Livewire\Component;

class TradeWorkspace extends Component
{
    public array $drafts = [];

    public ?int $editingId = null;

    public string $editCardType = '';

    public float $editAmountUsd = 0;

    public float $editRatePaid = 0;

    public string $editAlias = '';

    public bool $showManualModal = false;

    public string $manualPhone = '';

    public string $manualCardType = '';

    public float $manualAmountUsd = 0;

    public float $manualRatePaid = 0;

    public string $manualDescription = '';

    public string $manualStatus = 'approved';

    protected TradeDraftService $draftService;

    protected CashFlowService $cashFlowService;

    public function boot(): void
    {
        $this->draftService = app(TradeDraftService::class);
        $this->cashFlowService = app(CashFlowService::class);
    }

    public function mount(): void
    {
        $this->loadDrafts();
    }

    public function loadDrafts(): void
    {
        $this->drafts = Trade::with('user')
            ->where('status', TradeStatus::Draft)
            ->latest()
            ->get()
            ->toArray();
    }

    public function confirmDraft(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $this->draftService->confirmDraft($trade);
        $this->loadDrafts();
    }

    public function startEdit(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $this->editingId = $tradeId;
        $this->editCardType = $trade->card_type;
        $this->editAmountUsd = (float) $trade->amount_usd;
        $this->editRatePaid = (float) $trade->rate_paid;
        $this->editAlias = '';
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editAlias = '';
    }

    public function saveEdit(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $originalMessage = $trade->source_message ?? '';

        $this->draftService->editAndConfirmDraft($trade, [
            'card_type' => $this->editCardType,
            'amount_usd' => $this->editAmountUsd,
            'rate_paid' => $this->editRatePaid,
        ], $this->editAlias !== '' ? $this->editAlias : null);

        if ($this->editAlias !== '' && $originalMessage !== '') {
            $this->draftService->learnFromCorrection($originalMessage, $this->editCardType);
        }

        $this->editingId = null;
        $this->editAlias = '';
        $this->loadDrafts();
    }

    public function approveTrade(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $trade->update(['status' => TradeStatus::Approved]);
        $this->cashFlowService->onTradeApproved($trade);
        $this->loadDrafts();
    }

    public function manualSubmit(): void
    {
        $this->validate([
            'manualPhone' => 'required|string',
            'manualCardType' => 'required|string',
            'manualAmountUsd' => 'required|numeric|min:0.01',
            'manualRatePaid' => 'required|numeric|min:0.01|max:1',
            'manualStatus' => 'required|in:approved,manual',
        ]);

        $user = User::firstOrCreate(
            ['phone_number' => $this->manualPhone],
            ['name' => $this->manualPhone, 'email' => null],
        );

        $amountUsd = round($this->manualAmountUsd, 2);
        $ratePaid = round($this->manualRatePaid, 2);
        $customerPayout = round($amountUsd * $ratePaid, 2);
        $estimatedProfit = round($amountUsd - $customerPayout, 2);

        $status = $this->manualStatus === 'approved'
            ? TradeStatus::Approved
            : TradeStatus::Manual;

        $trade = Trade::create([
            'user_id' => $user->id,
            'card_type' => $this->manualCardType,
            'amount_usd' => $amountUsd,
            'rate_paid' => $ratePaid,
            'customer_payout' => $customerPayout,
            'estimated_profit' => $estimatedProfit,
            'status' => $status,
            'source' => 'manual',
            'source_message' => $this->manualDescription,
        ]);

        if ($status === TradeStatus::Approved) {
            $this->cashFlowService->onTradeApproved($trade);
        }

        $this->showManualModal = false;
        $this->resetManualForm();
        $this->loadDrafts();
    }

    private function resetManualForm(): void
    {
        $this->manualPhone = '';
        $this->manualCardType = '';
        $this->manualAmountUsd = 0;
        $this->manualRatePaid = 0;
        $this->manualDescription = '';
        $this->manualStatus = 'approved';
    }

    public function render()
    {
        return view('livewire.admin.trade-workspace');
    }
}
