<?php

namespace App\Models;

use App\Enums\ChatDirection;
use Database\Factories\ChatLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatLog extends Model
{
    /** @use HasFactory<ChatLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'direction',
        'message_body',
        'has_media',
        'media_path',
    ];

    protected function casts(): array
    {
        return [
            'direction' => ChatDirection::class,
            'has_media' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
