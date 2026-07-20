<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

class CustomersList extends Component
{
    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $editingCustomerId = null;

    public string $editingName = '';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function startEdit(int $id): void
    {
        $customer = User::find($id);
        $this->editingCustomerId = $id;
        $this->editingName = $customer?->name ?? '';
    }

    public function saveName(): void
    {
        $this->validate(['editingName' => 'required|string|max:255']);

        User::where('id', $this->editingCustomerId)->update(['name' => $this->editingName]);

        $this->editingCustomerId = null;
        $this->editingName = '';
    }

    public function cancelEdit(): void
    {
        $this->editingCustomerId = null;
        $this->editingName = '';
    }

    public function render()
    {
        $customers = User::whereNotNull('phone_number')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone_number', 'like', "%{$this->search}%");
            }))
            ->withCount('trades')
            ->withSum('trades', 'amount_usd')
            ->withSum('trades', 'estimated_profit')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.customers-list', ['customers' => $customers])
            ->layout('layouts.admin', ['title' => 'Customers']);
    }
}
