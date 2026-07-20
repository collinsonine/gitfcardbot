<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Rates</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage gift card exchange rates (₦ per 1 unit of currency)</p>
        </div>
    </div>

    <div class="bg-surface-card rounded-xl border border-gray-200 shadow-xs">
        <div class="px-4 sm:px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Card Rates</h2>
            <button wire:click="toggleForm"
                class="text-xs font-medium px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors border border-primary-700">
                {{ $showForm ? 'Cancel' : 'Add Card Type' }}
            </button>
        </div>

        <div class="p-4 sm:p-5">
            @if($showForm)
                <form wire:submit="save" class="mb-5 p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Card Name</label>
                        <input wire:model="cardName" placeholder="e.g. Amazon, Netflix, Spotify" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
                        @error('cardName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">USD → ₦</label>
                            <input wire:model="usdNgn" placeholder="0.00" type="number" step="0.01" min="0"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
                            @error('usdNgn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">GBP → ₦</label>
                            <input wire:model="gbpNgn" placeholder="0.00" type="number" step="0.01" min="0"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
                            @error('gbpNgn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">EUR → ₦</label>
                            <input wire:model="eurNgn" placeholder="0.00" type="number" step="0.01" min="0"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
                            @error('eurNgn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 border border-primary-700">
                        <span wire:loading.remove wire:target="save">{{ $editingRateId ? 'Update Card Type' : 'Save Card Type' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </form>
            @endif

            <div class="space-y-1">
                @forelse($rates as $r)
                    <div class="flex items-center justify-between py-3 px-3 rounded-lg hover:bg-gray-50 transition-colors -mx-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $r['card_name'] }}</p>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs text-gray-500">
                                    USD <span class="font-medium text-gray-700">₦{{ number_format($r['usd_ngn'], 2) }}</span>
                                </span>
                                <span class="text-xs text-gray-500">
                                    GBP <span class="font-medium text-gray-700">₦{{ number_format($r['gbp_ngn'], 2) }}</span>
                                </span>
                                <span class="text-xs text-gray-500">
                                    EUR <span class="font-medium text-gray-700">₦{{ number_format($r['eur_ngn'], 2) }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-1.5 shrink-0 ml-3">
                            <button wire:click="edit({{ $r['id'] }})" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-medium transition-colors border border-gray-200">Edit</button>
                            <button wire:click="delete({{ $r['id'] }})" wire:confirm="Delete this card type?" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-medium transition-colors border border-red-200">Delete</button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2 border border-gray-200">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        <p class="text-sm text-gray-400">No card types configured</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
