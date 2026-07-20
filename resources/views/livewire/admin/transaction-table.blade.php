<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-900">Transactions</h2>
        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg transition-colors font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-1 w-36 bg-white rounded-xl border border-gray-100 shadow-lg z-10 py-1">
                    <button wire:click="exportCsv" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">Export CSV</button>
                    <button wire:click="exportPdf" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">Export PDF</button>
                </div>
            </div>
        </div>
    </div>
    {{-- Aggregates --}}
    @if($context === 'trades')
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Total Volume</p>
                <p class="text-lg font-bold text-gray-900">${{ number_format($aggregates['total_volume'], 0) }}</p>
            </div>
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Total Profit</p>
                <p class="text-lg font-bold text-emerald-600">₦{{ number_format($aggregates['total_profit'], 0) }}</p>
            </div>
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Approved Vol.</p>
                <p class="text-lg font-bold text-gray-900">${{ number_format($aggregates['approved_volume'], 0) }}</p>
            </div>
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Approved Profit</p>
                <p class="text-lg font-bold text-emerald-600">₦{{ number_format($aggregates['approved_profit'], 0) }}</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Revenue</p>
                <p class="text-lg font-bold text-emerald-600">₦{{ number_format($aggregates['total_revenue'], 0) }}</p>
            </div>
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Cash Out</p>
                <p class="text-lg font-bold text-red-600">₦{{ number_format($aggregates['total_cash_out'], 0) }}</p>
            </div>
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Capital In</p>
                <p class="text-lg font-bold text-blue-600">₦{{ number_format($aggregates['total_capital'], 0) }}</p>
            </div>
            <div class="bg-surface-card rounded-xl border border-gray-100 p-4 shadow-xs">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Expenses</p>
                <p class="text-lg font-bold text-amber-600">₦{{ number_format($aggregates['total_expenses'], 0) }}</p>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="relative flex-1 max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..."
                class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
        </div>
        <div class="flex gap-1.5 flex-wrap">
            @if($context === 'trades')
                @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'declined' => 'Declined'] as $val => $label)
                    <button wire:click="$set('statusFilter', '{{ $val }}')"
                        class="px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $statusFilter === $val ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            @else
                @foreach(['' => 'All', 'trade' => 'Trades', 'cash_out' => 'Cash Out', 'revenue' => 'Revenue', 'capital_injection' => 'Capital In', 'expense' => 'Expense'] as $val => $label)
                    <button wire:click="$set('typeFilter', '{{ $val }}')"
                        class="px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $typeFilter === $val ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Period + Date Range --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-gray-400">Period:</span>
            @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'] as $key => $label)
                <button wire:click="setQuickPeriod('{{ $key }}')"
                    class="text-xs px-3 py-1.5 rounded-full font-medium transition-colors {{ $dateFrom === ($key === 'today' ? now()->toDateString() : ($key === 'week' ? now()->startOfWeek()->toDateString() : ($key === 'month' ? now()->startOfMonth()->toDateString() : null))) && (($key === 'all' && !$dateFrom && !$dateTo) || ($key !== 'all')) ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500 font-medium">From:</label>
            <input wire:model.live="dateFrom" type="date"
                class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500 font-medium">To:</label>
            <input wire:model.live="dateTo" type="date"
                class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
        </div>
        @if($typeFilter || $statusFilter || $search || $dateFrom || $dateTo || $userId)
            <button wire:click="clearFilters"
                class="text-xs px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition-colors font-medium">
                Clear Filters
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 bg-gray-50/50">
                        <th wire:click="sortBy('created_at')" class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-600">
                            Date
                            @if($sortField === 'created_at')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        @if($context === 'ledger')
                            <th class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                        @endif
                        <th class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden sm:table-cell">Customer</th>
                        <th class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Card</th>
                        <th class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden lg:table-cell">Source</th>
                        <th wire:click="sortBy('amount_usd')" class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-600">
                            Amount
                            @if($sortField === 'amount_usd')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Rate</th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Payout</th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden lg:table-cell">Profit</th>
                        <th class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden xl:table-cell">Description</th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $row)
                        @php
                            $statusStyles = match($row['status'] ?? null) {
                                'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                'completed' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                'declined' => 'bg-red-50 text-red-700 ring-red-600/20',
                                default => $row['status'] ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-gray-50 text-gray-500 ring-gray-400/20',
                            };
                            $typeColor = match($row['type']) {
                                'cash_out' => 'text-red-600',
                                'revenue' => 'text-emerald-600',
                                'capital_injection' => 'text-blue-600',
                                'expense' => 'text-amber-600',
                                'trade' => 'text-gray-600',
                                default => 'text-gray-600',
                            };
                            $sourceBadge = match($row['source'] ?? null) {
                                'manual' => 'bg-purple-50 text-purple-600 ring-purple-600/20',
                                'shadow_parser', 'shadow_manual' => 'bg-orange-50 text-orange-600 ring-orange-600/20',
                                'bot' => 'bg-sky-50 text-sky-600 ring-sky-600/20',
                                default => '',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 sm:px-5 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $row['date']->format('M j, g:ia') }}
                            </td>
                            @if($context === 'ledger')
                                <td class="px-4 sm:px-5 py-4">
                                    <span class="text-xs font-medium {{ $typeColor }}">{{ $row['type_label'] }}</span>
                                </td>
                            @endif
                            <td class="px-4 sm:px-5 py-4">
                                @if($row['status'])
                                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full ring-1 {{ $statusStyles }}">{{ ucfirst($row['status']) }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 hidden sm:table-cell">
                                @if($row['customer_name'])
                                    @if($row['customer_id'])
                                        <a href="{{ route('admin.chat', $row['customer_id']) }}" class="text-sm font-medium text-gray-900 hover:text-primary-600 transition-colors">{{ $row['customer_name'] }}</a>
                                    @else
                                        <span class="text-sm font-medium text-gray-900">{{ $row['customer_name'] }}</span>
                                    @endif
                                    @if($row['customer_phone'])
                                        <p class="text-xs text-gray-400">{{ $row['customer_phone'] }}</p>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-sm text-gray-500 hidden md:table-cell">
                                {{ $row['card_type'] ?? '—' }}
                            </td>
                            <td class="px-4 sm:px-5 py-4 hidden lg:table-cell">
                                @if($row['source'] && isset($sourceBadge))
                                    <span class="inline-block text-[10px] font-medium px-2 py-0.5 rounded-full ring-1 {{ $sourceBadge }}">{{ $row['source'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm font-medium text-gray-900">
                                @if($row['amount_usd'] !== null)
                                    ${{ number_format($row['amount_usd'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm text-gray-500 hidden md:table-cell">
                                @if($row['rate'] !== null)
                                    ₦{{ number_format($row['rate'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm text-gray-500 hidden md:table-cell">
                                @if($row['payout'] !== null)
                                    ₦{{ number_format($row['payout'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm font-medium hidden lg:table-cell">
                                @if($row['profit'] !== null)
                                    <span class="{{ $row['profit'] > 0 ? 'text-emerald-600' : ($row['profit'] < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                        {{ $row['profit'] > 0 ? '+' : '' }}₦{{ number_format($row['profit'], 2) }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-xs text-gray-400 hidden xl:table-cell max-w-[200px] truncate">
                                {{ $row['description'] ?? '—' }}
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-right" x-data="{ open: false }">
                                <button @click="open = true" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                    Delete
                                </button>
                                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                                    <div @click.outside="open = false" class="bg-white rounded-xl shadow-xl border border-gray-100 p-5 w-80">
                                        <p class="text-sm font-medium text-gray-900 mb-1">Delete this trade?</p>
                                        <p class="text-xs text-gray-500 mb-4">This will permanently remove the trade and all linked cash flow entries.</p>
                                        <div class="flex justify-end gap-2">
                                            <button @click="open = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
                                            <button @click="open = false; $wire.{{ $context === 'trades' ? 'deleteTrade' : 'deleteEntry' }}({{ $context === 'trades' ? $row['trade_id'] : $row['flow_id'] }})"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $context === 'ledger' ? 12 : 11 }}" class="px-5 py-12 text-center text-sm text-gray-400">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="px-4 sm:px-5 py-3 border-t border-gray-50">
                {{ $rows->links() }}
            </div>
        @endif
    </div>
</div>
