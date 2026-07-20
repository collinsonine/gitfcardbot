<div x-data="{ modalOpen: @entangle('showModal') }" x-cloak>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Accounting Ledger</h1>
            <p class="text-sm text-gray-500 mt-0.5">Financial overview and cash flow tracking</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="openModal"
                class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-colors font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Entry
            </button>
            <button wire:click="refreshStats" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl transition-colors font-medium disabled:opacity-50">
                <svg wire:loading.remove wire:target="refreshStats" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span wire:loading.remove wire:target="refreshStats">Refresh</span>
                <span wire:loading wire:target="refreshStats" class="flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </span>
            </button>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40" @click="$wire.set('showModal', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto border border-gray-200" @click.outside="$wire.set('showModal', false)">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">{{ $editingEntryId ? 'Edit Entry' : 'New Entry' }}</h2>
                <button @click="$wire.set('showModal', false)" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit="saveEntry" class="p-6 space-y-4">
                {{-- Entry Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Entry Type</label>
                    <select wire:model="entryType"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                        <option value="manual_trade">Manual Trade</option>
                        <option value="capital_injection">Capital Injection</option>
                        <option value="expense">Expense</option>
                    </select>
                    @error('entryType') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Manual Trade Fields --}}
                @if($entryType === 'manual_trade')
                    {{-- Card Type --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Card Type</label>
                        <select wire:model="cardType"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                            <option value="">Select card type</option>
                            @foreach($cardTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('cardType') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Currency</label>
                        <select wire:model.live="selectedCurrency"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                            <option value="usd">USD — US Dollar</option>
                            <option value="gbp">GBP — British Pound</option>
                            <option value="eur">EUR — Euro</option>
                        </select>
                    </div>

                    {{-- Customer --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Customer</label>
                        @if($customerId)
                            <div class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="text-sm font-medium text-gray-900 flex-1">{{ $customerSearch }}</span>
                                <button type="button" wire:click="clearCustomer" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            @error('customerId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @else
                            <div class="relative">
                                <input wire:model.live="customerSearch" type="text" placeholder="Search by name or phone..."
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                                @if(count($customerResults) > 0)
                                    <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        @foreach($customerResults as $customer)
                                            <button type="button" wire:click="selectCustomer({{ $customer['id'] }})"
                                                class="w-full text-left px-3 py-2.5 text-sm hover:bg-gray-50 transition-colors flex items-center gap-2 border-b border-gray-50 last:border-0">
                                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $customer['name'] }}</p>
                                                    <p class="text-xs text-gray-400">{{ $customer['phone'] }}</p>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="mt-2">
                                <button type="button" wire:click="toggleNewCustomer"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                    {{ $showNewCustomer ? 'Cancel — use existing customer' : '+ New customer' }}
                                </button>
                            </div>
                            @if($showNewCustomer)
                                <div class="mt-2 space-y-2 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                    <input wire:model="newCustomerName" type="text" placeholder="Full name"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                                    <input wire:model="newCustomerPhone" type="text" placeholder="Phone number (e.g. +234...)"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Amount & Rate --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Amount ({{ strtoupper($selectedCurrency) }})</label>
                            <input wire:model="amountUsd" type="number" step="0.01" min="0.01" placeholder="0.00"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                            @error('amountUsd') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Rate (₦/$1)</label>
                            <input wire:model="ratePaid" type="number" step="0.01" min="0.01" placeholder="0.00"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                            @error('ratePaid') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Computed Breakdown --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Customer Payout</span>
                            <span class="font-medium text-gray-900">₦{{ $computedPayout }}</span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-200 pt-2">
                            <span class="text-gray-500">Your Profit</span>
                            <span class="font-bold text-emerald-600">₦{{ $computedProfit }}</span>
                        </div>
                    </div>
                @else
                    {{-- Capital Injection / Expense Fields --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Amount (₦)</label>
                        <input wire:model="amountUsd" type="number" step="0.01" min="0.01" placeholder="0.00"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                        @error('amountUsd') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <input wire:model="entryDescription" type="text" placeholder="Optional note" maxlength="500"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                    @error('entryDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 border border-primary-700">
                        <span wire:loading.remove wire:target="saveEntry">{{ $editingEntryId ? 'Update Entry' : 'Save Entry' }}</span>
                        <span wire:loading wire:target="saveEntry" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Saving...
                        </span>
                    </button>
                    <button type="button" @click="$wire.set('showModal', false)"
                        class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <div class="bg-surface-card rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Paid Today</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-red-600">₦{{ number_format($todayPayout, 2) }}</p>
        </div>

        <div class="bg-surface-card rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Profit Today</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600">₦{{ number_format($todayProfit, 2) }}</p>
        </div>

        <div class="bg-surface-card rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Available Float</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold {{ $availableFloat >= 0 ? 'text-blue-600' : 'text-red-600' }}">₦{{ number_format($availableFloat, 2) }}</p>
        </div>
    </div>

    {{-- Macro Totals --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-surface-card rounded-xl border border-gray-200 p-3 text-center shadow-xs">
            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Total Revenue</p>
            <p class="text-sm font-bold text-emerald-600">₦{{ number_format($totalRevenue, 2) }}</p>
        </div>
        <div class="bg-surface-card rounded-xl border border-gray-200 p-3 text-center shadow-xs">
            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Total Cash Out</p>
            <p class="text-sm font-bold text-red-600">₦{{ number_format($totalCashOut, 2) }}</p>
        </div>
        <div class="bg-surface-card rounded-xl border border-gray-200 p-3 text-center shadow-xs">
            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Capital Injected</p>
            <p class="text-sm font-bold text-blue-600">₦{{ number_format($totalCapital, 2) }}</p>
        </div>
    </div>

    {{-- Transaction Table --}}
    <livewire:admin.transaction-table context="ledger" />

    {{-- User Lifetime Directory --}}
    <div class="bg-surface-card rounded-xl border border-gray-200 shadow-xs">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">Customer Directory</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($userDirectory as $user)
                <div class="px-4 py-3 hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $user['name'] }}</p>
                            <p class="text-xs text-gray-400">{{ $user['lifespan'] }} · {{ $user['trades_count'] }} trade{{ $user['trades_count'] !== 1 ? 's' : '' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-gray-900">${{ number_format($user['total_volume'], 0) }}</p>
                            <p class="text-xs font-medium text-emerald-600">+₦{{ number_format($user['total_profit'], 0) }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">
                    No customers yet
                </div>
            @endforelse
        </div>
    </div>
</div>
