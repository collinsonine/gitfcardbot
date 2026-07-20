<?php

namespace App\Models;

use App\Enums\CashFlowType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowLog extends Model
{
    protected $fillable = [
        'trade_id',
        'type',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashFlowType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }
}
