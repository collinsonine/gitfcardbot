<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Database\Factories\TradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    /** @use HasFactory<TradeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_type',
        'amount_usd',
        'rate_paid',
        'customer_payout',
        'estimated_profit',
        'status',
        'source',
        'source_message',
        'media_paths',
        'bank_details',
        'payment_receipt_path',
        'admin_notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'rate_paid' => 'decimal:2',
            'customer_payout' => 'decimal:2',
            'estimated_profit' => 'decimal:2',
            'status' => TradeStatus::class,
            'media_paths' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashFlowLogs(): HasMany
    {
        return $this->hasMany(CashFlowLog::class);
    }

    public function isDraft(): bool
    {
        return $this->status === TradeStatus::Draft;
    }

    public function recalculate(?float $rateOverride = null): void
    {
        $rate = $rateOverride ?? $this->rate_paid;
        $this->customer_payout = bcmul((string) $this->amount_usd, (string) $rate, 2);
        $this->estimated_profit = bcsub((string) $this->amount_usd, $this->customer_payout, 2);
    }
}
