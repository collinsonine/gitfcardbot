<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trades Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p { color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; text-align: left; padding: 8px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-approved { color: #059669; }
        .status-declined { color: #dc2626; }
        .status-pending { color: #d97706; }
    </style>
</head>
<body>
    <h1>Trades Export</h1>
    <p>Generated {{ $generatedAt }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Card</th>
                <th class="text-right">Amount (USD)</th>
                <th class="text-right">Rate (₦)</th>
                <th class="text-right">Payout (₦)</th>
                <th class="text-right">Profit (₦)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trades as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $t->user?->name ?? 'Unknown' }}</td>
                    <td>{{ $t->card_type }}</td>
                    <td class="text-right">${{ number_format($t->amount_usd, 2) }}</td>
                    <td class="text-right">₦{{ number_format($t->rate_paid, 2) }}</td>
                    <td class="text-right">₦{{ number_format($t->customer_payout, 2) }}</td>
                    <td class="text-right">₦{{ number_format($t->estimated_profit, 2) }}</td>
                    <td class="text-center {{ 'status-' . $t->status->value }}">{{ ucfirst($t->status->value) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #999; padding: 24px;">No trades found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
