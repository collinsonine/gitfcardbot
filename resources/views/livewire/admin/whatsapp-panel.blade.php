<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">WhatsApp Connection</h1>
            <p class="text-sm text-gray-500 mt-0.5">Status and management of the WhatsApp bridge service</p>
        </div>
        <div class="flex items-center gap-2">
            @if($status === 'connected')
                <button wire:click="disconnect" wire:confirm="Are you sure you want to disconnect WhatsApp? This will require re-scanning the QR code."
                    class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Disconnect
                </button>
            @endif
            <button wire:click="checkConnection" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                <span wire:loading.remove wire:target="checkConnection">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </span>
                <span wire:loading wire:target="checkConnection" class="flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Checking...
                </span>
            </button>
        </div>
    </div>

    <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs p-4 sm:p-6 max-w-lg">
        {{-- Status indicator --}}
        <div class="flex items-center gap-4 mb-6 p-4 rounded-xl {{ $statusClass }}">
            <div class="relative shrink-0">
                <div class="w-4 h-4 rounded-full {{ $dotClass }}"></div>
                @if($checking)
                    <div class="absolute inset-0 w-4 h-4 rounded-full bg-gray-400 animate-ping"></div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold {{ $statusTextClass }}">{{ $statusLabel }}</p>
                @if($phone)
                    <p class="text-xs mt-0.5 text-gray-500">Phone: <span class="font-medium">{{ $phone }}</span></p>
                @endif
            </div>
        </div>

        {{-- QR Code --}}
        @if(in_array($status, ['authenticating', 'disconnected']) && $qrCode)
            <div class="flex flex-col items-center mb-6">
                <img src="{{ $qrCode }}" alt="WhatsApp QR Code" class="w-48 h-48 rounded-xl border border-gray-200 bg-white p-2">
                <p class="text-xs text-gray-500 mt-3 text-center">
                    Scan this QR code with WhatsApp on your phone<br>
                    to authenticate the bridge connection.
                </p>
                <button wire:click="checkConnection" wire:loading.attr="disabled"
                    class="mt-3 text-xs text-primary-600 hover:text-primary-700 font-medium">
                    <span wire:loading.remove wire:target="checkConnection">Check connection status</span>
                    <span wire:loading wire:target="checkConnection">Checking...</span>
                </button>
            </div>
        @elseif($status === 'authenticating')
            <div wire:poll.5s="fetchQrCode" class="flex flex-col items-center mb-6">
                <div class="w-48 h-48 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center">
                    <div class="flex flex-col items-center gap-2 text-gray-400">
                        <svg class="animate-spin w-8 h-8" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span class="text-xs">Waiting for QR code...</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3 text-center">
                    The bridge is starting up. A QR code will appear here shortly.
                </p>
            </div>
        @endif

        {{-- Detail info --}}
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 px-3 bg-gray-50 rounded-lg">
                <span class="text-gray-500">Bridge URL</span>
                <span class="font-medium text-gray-900">{{ config('whatsapp.node_bridge_url', 'http://127.0.0.1:3001') }}</span>
            </div>
            @if($error)
                <div class="flex items-start gap-2 text-sm text-red-700 bg-red-50 rounded-xl px-4 py-3 mt-4">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p>{{ $error }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Broadcast --}}
    <div class="bg-surface-card rounded-xl border border-gray-100 shadow-xs p-4 sm:p-6 mt-6 max-w-lg">
        <h2 class="text-sm font-semibold text-gray-900 mb-1">Broadcast Message</h2>
        <p class="text-xs text-gray-500 mb-4">Send a message to all active customers.</p>

        <textarea wire:model="broadcastMessage" rows="4"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none"
            placeholder="Type your broadcast message here..."></textarea>

        @error('broadcastMessage') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

        <button wire:click="sendBroadcast" wire:loading.attr="disabled"
            class="mt-3 w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
            <span wire:loading.remove wire:target="sendBroadcast">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </span>
            <span wire:loading.remove wire:target="sendBroadcast">Send Broadcast</span>
            <span wire:loading wire:target="sendBroadcast">Sending...</span>
        </button>

        @if ($broadcastResults)
            <div class="mt-4 p-3 rounded-lg bg-gray-50 text-sm">
                <p class="font-medium text-gray-900">{{ $broadcastResults['sent'] }}/{{ $broadcastResults['total'] }} sent</p>
                @if ($broadcastResults['failed'] > 0)
                    <p class="text-xs text-red-500 mt-0.5">{{ $broadcastResults['failed'] }} failed</p>
                @endif
            </div>
        @endif
    </div>
</div>
