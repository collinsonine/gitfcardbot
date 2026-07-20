<div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
    <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
        <h2 class="text-sm font-semibold text-gray-900">Pending Trades</h2>
    </div>

    <div class="p-4 sm:p-5 space-y-3">
        @forelse($pendingTrades as $t)
            <div class="border border-gray-100 rounded-xl p-4">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $t['card_type'] }} · ${{ number_format($t['amount_usd'], 2) }}</p>
                        <p class="text-xs text-gray-400">{{ $t['user']['name'] ?? 'Unknown' }} · {{ $t['user']['phone_number'] ?? '' }}</p>
                    </div>
                    <span class="text-[10px] font-medium text-gray-400 bg-gray-100 px-2 py-1 rounded-full">#{{ $t['id'] }}</span>
                </div>

                <div class="grid grid-cols-3 gap-3 mb-3 text-xs">
                    <div class="bg-gray-50 rounded-lg px-3 py-2">
                        <p class="text-gray-400">Rate</p>
                        <p class="font-semibold text-gray-900">₦{{ number_format($t['rate_paid'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg px-3 py-2">
                        <p class="text-gray-400">Payout</p>
                        <p class="font-semibold text-gray-900">₦{{ number_format($t['customer_payout'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg px-3 py-2">
                        <p class="text-gray-400">Profit</p>
                        <p class="font-semibold text-emerald-600">₦{{ number_format($t['estimated_profit'], 2) }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button wire:click="approve({{ $t['id'] }})" wire:loading.attr="disabled" wire:target="approve({{ $t['id'] }})"
                        class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                        <span wire:loading.remove wire:target="approve({{ $t['id'] }})">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span wire:loading.remove wire:target="approve({{ $t['id'] }})">Approve</span>
                        <span wire:loading wire:target="approve({{ $t['id'] }})">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                        <span wire:loading wire:target="approve({{ $t['id'] }})">Approving</span>
                    </button>
                    <button wire:click="decline({{ $t['id'] }})" wire:loading.attr="disabled" wire:target="decline({{ $t['id'] }})"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                        <span wire:loading.remove wire:target="decline({{ $t['id'] }})">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </span>
                        <span wire:loading.remove wire:target="decline({{ $t['id'] }})">Decline</span>
                        <span wire:loading wire:target="decline({{ $t['id'] }})">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                        <span wire:loading wire:target="decline({{ $t['id'] }})">Declining</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm text-gray-400">All clear — no pending trades</p>
            </div>
        @endforelse
    </div>
</div>
