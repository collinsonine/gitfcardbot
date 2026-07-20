<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardAlias extends Model
{
    protected $fillable = [
        'alias_word',
        'resolved_card',
        'hit_count',
    ];

    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
        ];
    }

    public function incrementHit(): void
    {
        $this->increment('hit_count');
    }
}
