<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" class="p-1.5 -ml-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-base font-semibold text-gray-900">{{ $customer->name }}</h1>
                <p class="text-xs text-gray-400">{{ $customer->phone_number }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400">{{ $customer->is_bot_paused ? 'Manual' : 'Auto' }}</span>
            <button wire:click="toggleBotPause" wire:loading.attr="disabled"
                class="relative inline-flex h-6 w-10 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500/30 {{ $customer->is_bot_paused ? 'bg-red-400' : 'bg-emerald-400' }}">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200 {{ $customer->is_bot_paused ? 'translate-x-5' : 'translate-x-1' }}"></span>
            </button>
        </div>
    </div>

    {{-- Chat container --}}
    <div
        x-data="{ scrollEl: null }"
        x-init="scrollEl = $el.querySelector('.chat-messages'); scrollEl.scrollTop = scrollEl.scrollHeight"
        class="bg-surface-card rounded-xl border border-gray-100 shadow-xs flex flex-col h-[calc(100vh-12rem)] sm:h-[70vh]"
    >
        {{-- Messages --}}
        <div class="chat-messages flex-1 overflow-y-auto p-4 sm:p-5 space-y-3 scrollbar-thin scrollbar-thumb">
            @forelse($messages as $msg)
                @php $isInbound = $msg['direction'] === 'inbound'; @endphp
                <div class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }} items-end gap-2">
                    @if($isInbound)
                        <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center shrink-0 mb-1">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    @endif
                    <div class="max-w-[80%] sm:max-w-[70%] {{ $isInbound ? 'bg-gray-100 text-gray-900 rounded-2xl rounded-bl-md' : 'bg-primary-600 text-white rounded-2xl rounded-br-md' }} px-4 py-2.5 text-sm leading-relaxed">
                        <p>{{ $msg['message_body'] }}</p>
                        <p class="text-[10px] mt-1.5 {{ $isInbound ? 'text-gray-400' : 'text-primary-200' }} opacity-75">
                            {{ \Illuminate\Support\Carbon::parse($msg['created_at'])->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$isInbound)
                        <div class="w-7 h-7 rounded-full bg-primary-600 flex items-center justify-center shrink-0 mb-1">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    @endif
                </div>
            @empty
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="text-sm text-gray-400">No messages yet</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-100 p-3 sm:p-4">
            <form wire:submit="sendMessage" class="flex gap-2">
                <input wire:model="newMessage" wire:loading.attr="disabled" type="text" placeholder="Type your message..."
                    class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition-shadow placeholder:text-gray-300">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 flex items-center gap-1.5">
                    <span wire:loading.remove wire:target="sendMessage">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </span>
                    <span wire:loading wire:target="sendMessage">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
