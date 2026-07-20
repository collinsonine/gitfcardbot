<?php

namespace App\Livewire\Admin;

use App\Enums\TradeStatus;
use App\Models\Trade;
use Livewire\Component;
use Livewire\WithFileUploads;

class TradeDetail extends Component
{
    use WithFileUploads;

    public Trade $trade;

    public ?string $adminNotes = null;

    public $paymentReceipt = null;

    public function mount(Trade $trade): void
    {
        $this->trade = $trade->load('user');
        $this->adminNotes = $trade->admin_notes;
    }

    public function completeTrade(): void
    {
        $this->validate([
            'paymentReceipt' => 'nullable|image|max:5120',
        ]);

        $this->trade->update([
            'admin_notes' => $this->adminNotes,
            'status' => TradeStatus::Completed,
            'paid_at' => now(),
        ]);

        if ($this->paymentReceipt) {
            $path = $this->paymentReceipt->store('payment-receipts', 'public');
            $this->trade->update(['payment_receipt_path' => $path]);
        }

        $this->dispatch('trade-completed');
    }

    public function requestMedia(): void
    {
        $this->trade->update([
            'status' => TradeStatus::AwaitingMedia,
            'media_paths' => null,
        ]);

        $this->dispatch('media-requested');
    }

    public function render()
    {
        return view('livewire.admin.trade-detail')
            ->layout('layouts.admin', ['title' => "Trade #{$this->trade->id}"]);
    }
}
