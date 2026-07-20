<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Auto-Detected Drafts</h2>
            <p class="text-xs text-gray-400 mt-0.5">Parsed from WhatsApp messages</p>
        </div>
        <button wire:click="loadDrafts" wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium disabled:opacity-50">
            <svg wire:loading.remove wire:target="loadDrafts" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span wire:loading.remove wire:target="loadDrafts">Refresh</span>
            <svg wire:loading wire:target="loadDrafts" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </button>
    </div>

    {{-- Draft cards --}}
    <div class="space-y-3">
        @forelse($drafts as $draft)
            @php
                $isEditing = $this->editingId === $draft['id'];
            @endphp
            <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs overflow-hidden">
                @if($isEditing)
                    {{-- Edit mode --}}
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs font-medium text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full">Editing</span>
                            <span class="text-xs text-gray-400 truncate">{{ $draft['source_message'] ?? '' }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Card Type</label>
                                <select wire:model="editCardType" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                                    <option value="Amazon">Amazon</option>
                                    <option value="Apple">Apple</option>
                                    <option value="Google Play">Google Play</option>
                                    <option value="Steam">Steam</option>
                                    <option value="eBay">eBay</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Amount (USD)</label>
                                <input type="number" step="0.01" wire:model="editAmountUsd" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Rate Paid</label>
                                <input type="number" step="0.01" wire:model="editRatePaid" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">New Alias (optional)</label>
                                <input type="text" wire:model="editAlias" placeholder="e.g. steeam" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="saveEdit({{ $draft['id'] }})" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium disabled:opacity-50">
                                <span wire:loading.remove wire:target="saveEdit">Save & Confirm</span>
                                <svg wire:loading wire:target="saveEdit" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </button>
                            <button wire:click="cancelEdit" class="text-xs px-3 py-1.5 text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                        </div>
                    </div>
                @else
                    {{-- View mode --}}
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-900">{{ $draft['card_type'] }}</span>
                                    <span class="text-xs text-gray-400">·</span>
                                    <span class="text-sm font-bold text-primary-600">${{ number_format($draft['amount_usd'], 2) }}</span>
                                </div>
                                <p class="text-xs text-gray-400">
                                    {{ $draft['user']['name'] ?? 'Unknown' }} · Rate: {{ number_format($draft['rate_paid'], 2) }} · Payout: ₦{{ number_format($draft['customer_payout'], 2) }}
                                </p>
                                @if(!empty($draft['source_message']))
                                    <p class="text-[11px] text-gray-300 mt-1 italic truncate">"{{ $draft['source_message'] }}"</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-3">
                            <button wire:click="confirmDraft({{ $draft['id'] }})" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors font-medium disabled:opacity-50">
                                <svg wire:loading.remove wire:target="confirmDraft" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span wire:loading.remove wire:target="confirmDraft">Confirm & Post</span>
                                <svg wire:loading wire:target="confirmDraft" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </button>
                            <button wire:click="startEdit({{ $draft['id'] }})"
                                class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-10 bg-surface-card rounded-xl border border-gray-100">
                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-sm text-gray-400">No drafts yet</p>
                <p class="text-xs text-gray-300 mt-1">Messages from WhatsApp will appear here</p>
            </div>
        @endforelse
    </div>

    {{-- FAB --}}
    <div x-data="{ open: @js($showManualModal) }" x-on:open-manual-modal.window="open = true" x-on:close-manual-modal.window="open = false" class="fixed bottom-6 right-6 z-30 lg:hidden">
        <button @click="$dispatch('open-manual-modal')" class="w-14 h-14 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg flex items-center justify-center transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>

        {{-- Manual entry modal --}}
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="$dispatch('close-manual-modal')"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-5" @click.stop>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Manual Trade Entry</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Phone Number</label>
                        <input type="text" wire:model="manualPhone" placeholder="08012345678" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Card Type</label>
                            <select wire:model="manualCardType" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                                <option value="">Select...</option>
                                <option value="Amazon">Amazon</option>
                                <option value="Apple">Apple</option>
                                <option value="Google Play">Google Play</option>
                                <option value="Steam">Steam</option>
                                <option value="eBay">eBay</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Amount (USD)</label>
                            <input type="number" step="0.01" wire:model="manualAmountUsd" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Rate Paid</label>
                            <input type="number" step="0.01" wire:model="manualRatePaid" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                            <select wire:model="manualStatus" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                                <option value="approved">Approved</option>
                                <option value="manual">Manual (no ledger)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes (optional)</label>
                        <input type="text" wire:model="manualDescription" class="w-full text-sm border-gray-200 rounded-lg focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-5">
                    <button wire:click="manualSubmit" wire:loading.attr="disabled"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 text-sm px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                        <span wire:loading.remove wire:target="manualSubmit">Submit Trade</span>
                        <svg wire:loading wire:target="manualSubmit" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </button>
                    <button @click="$dispatch('close-manual-modal')" class="text-sm px-4 py-2.5 text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Desktop FAB --}}
    <div x-data="{ open: false }" x-on:open-manual-modal.window="open = true" x-on:close-manual-modal.window="open = false" class="fixed bottom-6 right-6 z-30 hidden lg:block">
        <button @click="$dispatch('open-manual-modal')" class="w-12 h-12 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
    </div>
</div>
