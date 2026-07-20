<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $context === 'trades' ? 'Trades' : 'Ledger' }} Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p { color: #666; margin-bottom: 8px; }
        .summary { display: flex; gap: 24px; margin-bottom: 16px; }
        .summary-box { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; }
        .summary-box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; }
        .summary-box .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .text-emerald { color: #059669; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }
        .text-amber { color: #d97706; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #f3f4f6; text-align: left; padding: 8px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-approved { color: #059669; font-weight: 600; }
        .status-completed { color: #2563eb; font-weight: 600; }
        .status-declined { color: #dc2626; font-weight: 600; }
        .status-pending { color: #d97706; font-weight: 600; }
    </style>
</head>
<body>
    <h1>{{ $context === 'trades' ? 'Trades' : 'Ledger' }} Export</h1>
    <p>Generated {{ $generatedAt }} @if(count($rows)) · {{ count($rows) }} entries @endif</p>

    <div class="summary">
        @if($context === 'trades')
            <div class="summary-box">
                <div class="label">Total Volume</div>
                <div class="value">${{ number_format($aggregates['total_volume'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Total Profit</div>
                <div class="value text-emerald">₦{{ number_format($aggregates['total_profit'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Approved Volume</div>
                <div class="value">${{ number_format($aggregates['approved_volume'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Approved Profit</div>
                <div class="value text-emerald">₦{{ number_format($aggregates['approved_profit'], 2) }}</div>
            </div>
        @else
            <div class="summary-box">
                <div class="label">Revenue</div>
                <div class="value text-emerald">₦{{ number_format($aggregates['total_revenue'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Cash Out</div>
                <div class="value text-red">₦{{ number_format($aggregates['total_cash_out'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Capital In</div>
                <div class="value text-blue">₦{{ number_format($aggregates['total_capital'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Expenses</div>
                <div class="value text-amber">₦{{ number_format($aggregates['total_expenses'], 2) }}</div>
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                @if($context === 'ledger')
                    <th>Type</th>
                @endif
                <th>Status</th>
                <th>Customer</th>
                <th>Card</th>
                <th>Source</th>
                <th class="text-right">Amount (USD)</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Payout (₦)</th>
                <th class="text-right">Profit (₦)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['date']->format('Y-m-d H:i') }}</td>
                    @if($context === 'ledger')
                        <td>{{ $row['type_label'] }}</td>
                    @endif
                    <td class="{{ isset($row['status']) ? 'status-' . $row['status'] : '' }}">{{ ucfirst($row['status'] ?? '—') }}</td>
                    <td>{{ $row['customer_name'] ?? '—' }}</td>
                    <td>{{ $row['card_type'] ?? '—' }}</td>
                    <td>{{ $row['source'] ?? '—' }}</td>
                    <td class="text-right">@if($row['amount_usd'] !== null)${{ number_format($row['amount_usd'], 2) }}@else—@endif</td>
                    <td class="text-right">@if($row['rate'] !== null)₦{{ number_format($row['rate'], 2) }}@else—@endif</td>
                    <td class="text-right">@if($row['payout'] !== null)₦{{ number_format($row['payout'], 2) }}@else—@endif</td>
                    <td class="text-right">@if($row['profit'] !== null)₦{{ number_format($row['profit'], 2) }}@else—@endif</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $context === 'ledger' ? 10 : 9 }}" style="text-align: center; color: #999; padding: 24px;">No transactions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
