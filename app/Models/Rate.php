<?php

namespace App\Models;

use Database\Factories\RateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    /** @use HasFactory<RateFactory> */
    use HasFactory;

    protected $fillable = [
        'card_name',
        'usd_ngn',
        'gbp_ngn',
        'eur_ngn',
    ];

    protected function casts(): array
    {
        return [
            'usd_ngn' => 'decimal:2',
            'gbp_ngn' => 'decimal:2',
            'eur_ngn' => 'decimal:2',
        ];
    }
}
