<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Overview of your gift card trading business</p>
        </div>
        <button wire:click="refreshData" wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
            <svg wire:loading.remove wire:target="refreshData" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span wire:loading.remove wire:target="refreshData">Refresh</span>
            <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Refreshing...
            </span>
        </button>
    </div>

    {{-- Metric cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="bg-surface-card rounded-xl border border-gray-100 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Volume</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-gray-900">${{ number_format($totals['volume'] ?? 0, 0) }}</p>
        </div>

        <div class="bg-surface-card rounded-xl border border-gray-100 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Net Profit</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600">₦{{ number_format($totals['profit'] ?? 0, 0) }}</p>
        </div>

        <div class="bg-surface-card rounded-xl border border-gray-100 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-amber-600">{{ $totals['pending_count'] ?? 0 }}</p>
        </div>

        <div class="bg-surface-card rounded-xl border border-gray-100 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Drafts</span>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-purple-600">{{ $draftCount }}</p>
        </div>
    </div>

    {{-- Trade Workspace --}}
    <div class="mb-6">
        <livewire:admin.trade-workspace />
    </div>

    {{-- Panels --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6">
        {{-- Customer Lifespan --}}
        <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
            <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">Customer Lifespan</h2>
            </div>
            <div class="p-4 sm:p-5">
                <div class="space-y-1">
                    @forelse($customerLifespans as $c)
                        <div class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 transition-colors -mx-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $c['name'] }}</p>
                                <p class="text-xs text-gray-400">{{ $c['lifetime'] }} · {{ $c['trades_count'] }} trade{{ $c['trades_count'] !== 1 ? 's' : '' }}</p>
                            </div>
                            <div class="text-right ml-3 shrink-0">
                                <p class="text-sm font-semibold text-gray-900">${{ number_format($c['total_volume'], 0) }}</p>
                                <p class="text-xs font-medium text-emerald-600">+₦{{ number_format($c['total_profit'], 0) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <p class="text-sm text-gray-400">No customers yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Trades --}}
        <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
            <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">Recent Trades</h2>
            </div>
            <div class="p-4 sm:p-5">
                <div class="space-y-1">
                    @forelse($recentTrades as $t)
                        @php
                            $statusColor = match($t['status']) {
                                'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                'declined' => 'bg-red-50 text-red-700 ring-red-600/20',
                                'draft' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                            };
                        @endphp
                        <div class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 transition-colors -mx-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $t['card_type'] }} · ${{ number_format($t['amount_usd'], 0) }}</p>
                                <p class="text-xs text-gray-400">{{ $t['user']['name'] ?? 'Unknown' }}</p>
                            </div>
                            <span class="shrink-0 ml-3 text-xs font-medium px-2.5 py-1 rounded-full ring-1 {{ $statusColor }}">{{ ucfirst($t['status']) }}</span>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <p class="text-sm text-gray-400">No trades yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom panels --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <livewire:admin.trade-approval />
    </div>
</div>
