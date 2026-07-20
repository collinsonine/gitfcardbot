<?php

namespace App\Models;

use App\Enums\ChatState;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone_number', 'whatsapp_id', 'chat_state', 'invalid_option_count', 'is_bot_paused', 'trade_draft', 'pending_card_type', 'pending_amount', 'pending_context_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_bot_paused' => 'boolean',
            'invalid_option_count' => 'integer',
            'chat_state' => ChatState::class,
            'trade_draft' => 'array',
        ];
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function chatLogs(): HasMany
    {
        return $this->hasMany(ChatLog::class);
    }

    public function dominantCardType(): ?string
    {
        $result = $this->trades()
            ->where('status', '!=', 'declined')
            ->selectRaw('card_type, COUNT(*) as trade_count')
            ->groupBy('card_type')
            ->orderByDesc('trade_count')
            ->first();

        if (! $result) {
            return null;
        }

        $totalTrades = $this->trades()->where('status', '!=', 'declined')->count();

        if ($totalTrades === 0) {
            return null;
        }

        $ratio = $result->trade_count / $totalTrades;

        return $ratio >= 0.8 ? $result->card_type : null;
    }
}
