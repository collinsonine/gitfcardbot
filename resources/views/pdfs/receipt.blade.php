<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1a1a1a; margin: 0; padding: 30px; }
        h1 { font-size: 20px; margin: 0 0 5px; }
        .subtitle { color: #6b7280; font-size: 11px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { font-size: 10px; text-transform: uppercase; color: #6b7280; background: #f9fafb; }
        td { font-size: 12px; }
        .amount { text-align: right; font-weight: bold; }
        .total-row td { border-top: 2px solid #1a1a1a; font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #6b7280; text-align: center; }
        .badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .details p { margin: 3px 0; }
    </style>
</head>
<body>
    <h1>Payment Receipt</h1>
    <p class="subtitle">Trade #{{ $trade->id }} &middot; {{ now()->format('M d, Y') }}</p>

    <table>
        <tr>
            <th style="width: 30%">Card Type</th>
            <td>{{ $trade->card_type }}</td>
        </tr>
        <tr>
            <th>Amount (USD)</th>
            <td>${{ number_format($trade->amount_usd, 2) }}</td>
        </tr>
        <tr>
            <th>Exchange Rate</th>
            <td>₦{{ number_format($trade->rate_paid, 2) }}</td>
        </tr>
        <tr>
            <th>Customer</th>
            <td>{{ $trade->user->name ?? 'N/A' }} ({{ $trade->user->phone_number }})</td>
        </tr>
        <tr>
            <th>Status</th>
            <td><span class="badge">PAID</span></td>
        </tr>
        @if ($trade->admin_notes)
            <tr>
                <th>Notes</th>
                <td>{{ $trade->admin_notes }}</td>
            </tr>
        @endif
    </table>

    <table>
        <tr>
            <th>Description</th>
            <th class="amount">Amount</th>
        </tr>
        <tr>
            <td>Gift Card Value</td>
            <td class="amount">${{ number_format($trade->amount_usd, 2) }}</td>
        </tr>
        <tr>
            <td>Payout</td>
            <td class="amount">₦{{ number_format($trade->customer_payout, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <p>This is an auto-generated receipt. No signature required.</p>
        <p>Thank you for trading with us!</p>
    </div>
</body>
</html>
