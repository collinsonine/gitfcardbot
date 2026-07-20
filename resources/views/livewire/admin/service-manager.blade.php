<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Services</h1>
            <p class="text-sm text-gray-500 mt-0.5">Monitor and control system services</p>
        </div>
        <button wire:click="checkAll" wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
            <svg wire:loading.remove wire:target="checkAll" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span wire:loading.remove wire:target="checkAll">Refresh</span>
            <span wire:loading wire:target="checkAll" class="flex items-center gap-1.5">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Checking...
            </span>
        </button>
    </div>

    {{-- Overall Status --}}
    @php
        $overallColor = match($overallStatus) {
            'healthy' => 'bg-emerald-50 border-emerald-200',
            'degraded' => 'bg-amber-50 border-amber-200',
            default => 'bg-gray-50 border-gray-200',
        };
        $overallDot = match($overallStatus) {
            'healthy' => 'bg-emerald-500',
            'degraded' => 'bg-amber-500',
            default => 'bg-gray-400',
        };
        $overallLabel = match($overallStatus) {
            'healthy' => 'All Systems Operational',
            'degraded' => 'Some Services Degraded',
            default => 'Status Unknown',
        };
    @endphp
    <div class="{{ $overallColor }} border rounded-xl p-4 mb-6 flex items-center gap-3">
        <span class="w-3 h-3 rounded-full {{ $overallDot }} shrink-0"></span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900">{{ $overallLabel }}</p>
            @if($lastCheck)
                <p class="text-xs text-gray-500">Last checked: {{ $lastCheck }}</p>
            @endif
        </div>
        <button wire:click="checkAll" wire:loading.attr="disabled" wire:target="checkAll"
            class="text-xs text-gray-500 hover:text-gray-700 transition-colors disabled:opacity-50">
            <svg wire:loading.remove wire:target="checkAll" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <svg wire:loading wire:target="checkAll" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </button>
    </div>

    {{-- Auto-poll every 15s --}}
    <div wire:poll.15s="checkAll"></div>

    {{-- Service Cards --}}
    <div class="space-y-4">

        {{-- WhatsApp Bridge --}}
        @php
            $bridgeColor = match($bridgeHealth['status'] ?? 'offline') {
                'online' => match($bridgeHealth['bridge_status'] ?? '') {
                    'connected' => 'border-emerald-200',
                    'awaiting_qr', 'initializing' => 'border-amber-200',
                    default => 'border-gray-200',
                },
                default => 'border-red-200',
            };
            $bridgeDot = match($bridgeHealth['status'] ?? 'offline') {
                'online' => match($bridgeHealth['bridge_status'] ?? '') {
                    'connected' => 'bg-emerald-500',
                    'awaiting_qr', 'initializing' => 'bg-amber-500 animate-pulse',
                    'restarting' => 'bg-blue-500 animate-pulse',
                    default => 'bg-gray-400',
                },
                default => 'bg-red-500',
            };
        @endphp
        <div class="bg-surface-card rounded-xl border {{ $bridgeColor }} shadow-xs overflow-hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">WhatsApp Bridge</h3>
                            <p class="text-xs text-gray-400">Node.js + whatsapp-web.js</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $bridgeDot }}"></span>
                        <span class="text-xs font-medium text-gray-600">
                            {{ $whatsappStatus['label'] ?? 'Unknown' }}
                        </span>
                    </div>
                </div>

                @if(($bridgeHealth['status'] ?? '') === 'online')
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                        <div class="bg-gray-50 rounded-lg px-3 py-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Uptime</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $bridgeHealth['uptime_human'] ?? '0s' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Memory</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $bridgeHealth['memory_mb'] ?? 0 }} MB</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Messages</p>
                            <p class="text-sm font-semibold text-gray-900">{{ number_format($bridgeHealth['messages_processed'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Node.js</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $bridgeHealth['node_version'] ?? '?' }}</p>
                        </div>
                    </div>

                    @if(!empty($bridgeHealth['phone']))
                        <p class="text-xs text-gray-500 mb-3">
                            Phone: <span class="font-medium text-gray-700">{{ $bridgeHealth['phone'] }}</span>
                            @if(!empty($bridgeHealth['started_at']))
                                · Started: <span class="text-gray-500">{{ \Carbon\Carbon::parse($bridgeHealth['started_at'])->diffForHumans() }}</span>
                            @endif
                        </p>
                    @endif
                @else
                    <div class="bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-3">
                        <p class="text-xs text-red-600 font-medium">Bridge is offline</p>
                        <p class="text-[11px] text-red-400 mt-0.5 font-mono">cd bridge && node bot.js</p>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <button wire:click="restartBridge" wire:loading.attr="disabled"
                        @if(($bridgeHealth['status'] ?? 'offline') === 'offline') disabled @endif
                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg transition-colors font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="restartBridge" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span wire:loading.remove wire:target="restartBridge">Restart Client</span>
                        <svg wire:loading wire:target="restartBridge" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span wire:loading wire:target="restartBridge">Restarting...</span>
                    </button>
                    @if(!empty($restartMessage))
                        <span class="text-xs {{ str_contains($restartMessage, 'initiated') ? 'text-emerald-600' : 'text-red-500' }}">{{ $restartMessage }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Laravel Queue Worker --}}
        @php
            $queueColor = match($queueStatus['status'] ?? 'unknown') {
                'running' => 'border-emerald-200',
                'stopped' => 'border-red-200',
                default => 'border-gray-200',
            };
            $queueDot = match($queueStatus['status'] ?? 'unknown') {
                'running' => 'bg-emerald-500',
                'stopped' => 'bg-red-500',
                default => 'bg-gray-400',
            };
        @endphp
        <div class="bg-surface-card rounded-xl border {{ $queueColor }} shadow-xs">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Queue Worker</h3>
                            <p class="text-xs text-gray-400">Laravel queue:work process</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $queueDot }}"></span>
                        <span class="text-xs font-medium text-gray-600">{{ $queueStatus['label'] ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Laravel Scheduler --}}
        @php
            $schedColor = match($schedulerStatus['status'] ?? 'unknown') {
                'running' => 'border-emerald-200',
                'stopped' => 'border-amber-200',
                default => 'border-gray-200',
            };
            $schedDot = match($schedulerStatus['status'] ?? 'unknown') {
                'running' => 'bg-emerald-500',
                'stopped' => 'bg-amber-500',
                default => 'bg-gray-400',
            };
        @endphp
        <div class="bg-surface-card rounded-xl border {{ $schedColor }} shadow-xs">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Scheduler</h3>
                            <p class="text-xs text-gray-400">Laravel task scheduler</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $schedDot }}"></span>
                        <span class="text-xs font-medium text-gray-600">{{ $schedulerStatus['label'] ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Setup Instructions --}}
    <div class="mt-6 bg-surface-card rounded-xl border border-gray-100 shadow-xs">
        <div class="px-4 py-3 border-b border-gray-50">
            <h2 class="text-sm font-semibold text-gray-900">Process Management</h2>
        </div>
        <div class="p-4 space-y-3 text-xs text-gray-600 leading-relaxed">
            <p>For automatic restart on crash, use <strong>PM2</strong> to manage the bridge process:</p>
            <div class="bg-gray-900 text-gray-100 rounded-lg p-3 font-mono text-[11px] overflow-x-auto">
                <code>cd bridge && pm2 start ecosystem.config.cjs && pm2 save</code>
            </div>
            <p>For auto-start on server boot:</p>
            <div class="bg-gray-900 text-gray-100 rounded-lg p-3 font-mono text-[11px] overflow-x-auto">
                <code>pm2 startup && pm2 save</code>
            </div>
            <p>For the Laravel scheduler, add to crontab:</p>
            <div class="bg-gray-900 text-gray-100 rounded-lg p-3 font-mono text-[11px] overflow-x-auto">
                <code>* * * * * cd /path/to/giftcardbot && php artisan schedule:run >> /dev/null 1>&amp;2</code>
            </div>
        </div>
    </div>
</div>
