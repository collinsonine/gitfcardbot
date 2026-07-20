<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Bot Responses</h1>
        <p class="text-sm text-gray-500 mt-0.5">Customize the automated replies the bot sends to customers</p>
    </div>

    {{-- Editor --}}
    @if($showEditor)
        <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs p-4 sm:p-6 mb-6 max-w-2xl">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $editKey) }}</h2>
                    @if($editDescription)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $editDescription }}</p>
                    @endif
                </div>
                <span class="text-[10px] font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded">{{ $editKey }}</span>
            </div>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Message</label>
                    <textarea wire:model="editMessage" rows="6"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow font-mono"></textarea>
                    @error('editMessage') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(admin only)</span></label>
                    <input wire:model="editDescription" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow">
                </div>
                <div class="flex gap-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50">
                        <span wire:loading.remove>Save Response</span>
                        <span wire:loading>Saving...</span>
                    </button>
                    <button type="button" wire:click="$set('showEditor', false)"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Response list --}}
    <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="divide-y divide-gray-50">
            @forelse($responses as $r)
                <div class="p-4 sm:p-5 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $r['key']) }}</h3>
                                @if($r['is_custom'])
                                    <span class="text-[10px] font-medium text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full">custom</span>
                                @endif
                            </div>
                            @if($r['description'])
                                <p class="text-xs text-gray-400 mb-2">{{ $r['description'] }}</p>
                            @endif
                            <pre class="text-xs text-gray-600 bg-gray-50 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">{{ $r['message'] }}</pre>
                            <span class="text-[10px] font-mono text-gray-300 mt-1 block">{{ $r['key'] }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 shrink-0">
                            <button wire:click="edit('{{ $r['key'] }}')"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-medium transition-colors">
                                Edit
                            </button>
                            @if($r['is_custom'])
                                <button wire:click="resetToDefault('{{ $r['key'] }}')" wire:confirm="Reset this response to default?"
                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-medium transition-colors">
                                    Reset
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-sm text-gray-400">No responses found.</div>
            @endforelse
        </div>
    </div>
</div>
