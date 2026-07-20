<?php

namespace App\Livewire\Admin;

use App\Models\Rate;
use Livewire\Component;

class RatesManager extends Component
{
    public array $rates = [];

    public string $cardName = '';

    public string $usdNgn = '';

    public string $gbpNgn = '';

    public string $eurNgn = '';

    public ?int $editingRateId = null;

    public bool $showForm = false;

    public function mount(): void
    {
        $this->loadRates();
    }

    public function loadRates(): void
    {
        $this->rates = Rate::all()->toArray();
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;

        if (! $this->showForm) {
            $this->resetForm();
        }
    }

    public function edit(int $rateId): void
    {
        $rate = Rate::findOrFail($rateId);
        $this->editingRateId = $rate->id;
        $this->cardName = $rate->card_name;
        $this->usdNgn = (string) $rate->usd_ngn;
        $this->gbpNgn = (string) $rate->gbp_ngn;
        $this->eurNgn = (string) $rate->eur_ngn;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'cardName' => 'required|string|max:255',
            'usdNgn' => 'required|numeric|min:0|max:99999999.99',
            'gbpNgn' => 'required|numeric|min:0|max:99999999.99',
            'eurNgn' => 'required|numeric|min:0|max:99999999.99',
        ]);

        $data = [
            'card_name' => $this->cardName,
            'usd_ngn' => $this->usdNgn,
            'gbp_ngn' => $this->gbpNgn,
            'eur_ngn' => $this->eurNgn,
        ];

        if ($this->editingRateId) {
            Rate::findOrFail($this->editingRateId)->update($data);
        } else {
            Rate::create($data);
        }

        $this->resetForm();
        $this->loadRates();
    }

    public function delete(int $rateId): void
    {
        Rate::findOrFail($rateId)->delete();
        $this->loadRates();
    }

    private function resetForm(): void
    {
        $this->cardName = '';
        $this->usdNgn = '';
        $this->gbpNgn = '';
        $this->eurNgn = '';
        $this->editingRateId = null;
        $this->showForm = false;
    }

    public function render()
    {
        return view('livewire.admin.rates-manager')
            ->layout('layouts.admin', ['title' => 'Rates']);
    }
}
