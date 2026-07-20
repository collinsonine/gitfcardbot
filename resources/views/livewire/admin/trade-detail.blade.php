<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Trade #{{ $trade->id }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $trade->card_type }} · ${{ number_format($trade->amount_usd, 2) }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.trades') }}" class="text-sm px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors font-medium">Back to Trades</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Trade Info --}}
            <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
                <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                    <h2 class="text-sm font-semibold text-gray-900">Trade Details</h2>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-400">Card Type</p>
                            <p class="font-medium text-gray-900">{{ $trade->card_type }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Amount (USD)</p>
                            <p class="font-medium text-gray-900">${{ number_format($trade->amount_usd, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Rate Paid</p>
                            <p class="font-medium text-gray-900">₦{{ number_format($trade->rate_paid, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Customer Payout</p>
                            <p class="font-medium text-emerald-600">₦{{ number_format($trade->customer_payout, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Profit</p>
                            <p class="font-medium text-emerald-600">₦{{ number_format($trade->estimated_profit, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Status</p>
                            @php
                                $statusColor = match ($trade->status->value) {
                                    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'approved', 'awaiting_bank_details' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'declined' => 'bg-red-50 text-red-700 ring-red-600/20',
                                    default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                };
                            @endphp
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full ring-1 {{ $statusColor }}">{{ str_replace('_', ' ', ucfirst($trade->status->value)) }}</span>
                        </div>
                        <div>
                            <p class="text-gray-400">Date</p>
                            <p class="font-medium text-gray-900">{{ $trade->created_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @if ($trade->paid_at)
                            <div>
                                <p class="text-gray-400">Paid At</p>
                                <p class="font-medium text-gray-900">{{ $trade->paid_at->format('M d, Y g:i A') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Media --}}
            @if ($trade->media_paths)
                <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
                    <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">User Media</h2>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach ((array) $trade->media_paths as $path)
                                <div class="aspect-square bg-gray-50 rounded-xl overflow-hidden border border-gray-100">
                                    <img src="{{ asset('storage/' . $path) }}" alt="Media" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Bank Details --}}
            @if ($trade->bank_details)
                <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
                    <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">Bank Details</h2>
                    </div>
                    <div class="p-4 sm:p-5">
                        <pre class="text-sm text-gray-900 whitespace-pre-wrap font-sans">{{ $trade->bank_details }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
                <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                    <h2 class="text-sm font-semibold text-gray-900">Customer</h2>
                </div>
                <div class="p-4 sm:p-5 space-y-3 text-sm">
                    <div>
                        <p class="text-gray-400">Name</p>
                        <p class="font-medium text-gray-900">{{ $trade->user->name ?? 'Unknown' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Phone</p>
                        <p class="font-medium text-gray-900">{{ $trade->user->phone_number }}</p>
                    </div>
                    <a href="{{ route('admin.chat', $trade->user->id) }}" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition-colors font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        Open Chat
                    </a>
                </div>
            </div>

            {{-- Request Media --}}
            @if (in_array($trade->status, [\App\Enums\TradeStatus::Pending, \App\Enums\TradeStatus::AwaitingMedia, \App\Enums\TradeStatus::Declined, \App\Enums\TradeStatus::AwaitingBankDetails]))
                <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
                    <div class="p-4 sm:p-5">
                        <p class="text-xs text-gray-500 mb-3">Ask the user to send gift card / receipt photos again.</p>
                        <button wire:click="requestMedia" wire:loading.attr="disabled"
                            class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                            <span wire:loading.remove wire:target="requestMedia">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3M16 8l-4-4m0 0l-4 4m4-4v12"/></svg>
                            </span>
                            <span wire:loading.remove wire:target="requestMedia">Request Media Again</span>
                            <span wire:loading wire:target="requestMedia">Sending...</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Complete Payout --}}
            @if ($trade->status === \App\Enums\TradeStatus::Approved || $trade->status === \App\Enums\TradeStatus::AwaitingBankDetails)
                <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs">
                    <div class="px-4 sm:px-5 py-4 border-b border-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">Complete Payout</h2>
                    </div>
                    <div class="p-4 sm:p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Admin Notes</label>
                            <textarea wire:model="adminNotes" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Payment Receipt (image)</label>
                            <input type="file" wire:model="paymentReceipt" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            @error('paymentReceipt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            <div wire:loading wire:target="paymentReceipt" class="text-xs text-gray-400 mt-1">Uploading...</div>
                        </div>

                        <button wire:click="completeTrade" wire:loading.attr="disabled"
                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                            <span wire:loading.remove wire:target="completeTrade">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span wire:loading.remove wire:target="completeTrade">Complete Payout</span>
                            <span wire:loading wire:target="completeTrade">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </span>
                            <span wire:loading wire:target="completeTrade">Processing...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
