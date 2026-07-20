<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Customers</h1>
            <p class="text-sm text-gray-500 mt-0.5">All WhatsApp customers and their trade activity</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <div class="relative max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search customers..."
                class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 bg-gray-50/50">
                        <th wire:click="sortBy('name')" class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-600">
                            <div class="flex items-center gap-1">Name @include('components.sort-icon', ['field' => 'name'])</div>
                        </th>
                        <th wire:click="sortBy('phone_number')" class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-600 hidden sm:table-cell">
                            <div class="flex items-center gap-1">Phone @include('components.sort-icon', ['field' => 'phone_number'])</div>
                        </th>
                        <th wire:click="sortBy('created_at')" class="text-left px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-600 hidden md:table-cell">
                            <div class="flex items-center gap-1">Joined @include('components.sort-icon', ['field' => 'created_at'])</div>
                        </th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Trades</th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Volume</th>
                        <th class="text-right px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Profit</th>
                        <th class="text-center px-4 sm:px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($customers as $c)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 sm:px-5 py-4">
                                @if ($editingCustomerId === $c->id)
                                    <div class="flex items-center gap-1">
                                        <input wire:model="editingName" wire:keydown.enter="saveName" wire:keydown.escape="cancelEdit" wire:blur="saveName" type="text" autofocus
                                            class="w-40 rounded-lg border border-primary-300 px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                                        <button wire:click="saveName" class="p-1 text-emerald-600 hover:text-emerald-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button wire:click="cancelEdit" class="p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2.5 group">
                                        <a href="{{ route('admin.chat', $c->id) }}" class="flex items-center gap-2.5 group">
                                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center shrink-0">
                                                <span class="text-xs font-semibold text-primary-700">{{ strtoupper(substr($c->name, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 group-hover:text-primary-600 transition-colors">{{ $c->name }}</p>
                                                <p class="text-xs text-gray-400 sm:hidden">{{ $c->phone_number }}</p>
                                            </div>
                                        </a>
                                        <button wire:click="startEdit({{ $c->id }})" class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-primary-600 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-4 text-sm text-gray-500 hidden sm:table-cell">{{ $c->phone_number }}</td>
                            <td class="px-4 sm:px-5 py-4 text-sm text-gray-500 hidden md:table-cell">{{ $c->created_at->diffForHumans() }}</td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm font-medium text-gray-900">{{ $c->trades_count }}</td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm font-medium text-gray-900">${{ number_format($c->trades_sum_amount_usd ?? 0, 0) }}</td>
                            <td class="px-4 sm:px-5 py-4 text-right text-sm font-medium text-emerald-600">+₦{{ number_format($c->trades_sum_estimated_profit ?? 0, 0) }}</td>
                            <td class="px-4 sm:px-5 py-4 text-center">
                                <a href="{{ route('admin.trades', ['user_id' => $c->id]) }}"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    View Trades
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="px-4 sm:px-5 py-3 border-t border-gray-50">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
