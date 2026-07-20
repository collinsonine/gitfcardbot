<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                Trades
                @if($customerName)
                    <span class="text-base font-normal text-gray-400">— {{ $customerName }}</span>
                @endif
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                @if($customerName)
                    Trade history for {{ $customerName }}
                @else
                    All gift card trades
                @endif
            </p>
        </div>
    </div>

    <livewire:admin.transaction-table context="trades" :userId="$userId" :dateFrom="$dateFrom" :dateTo="$dateTo" />
</div>
