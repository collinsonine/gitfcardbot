<?php

namespace App\Models;

use App\StateMachines\ChatStateMachine;
use Database\Factories\BotResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotResponse extends Model
{
    /** @use HasFactory<BotResponseFactory> */
    use HasFactory;

    protected $fillable = ['key', 'message', 'description'];

    public static function text(string $key): string
    {
        return static::where('key', $key)->value('message') ?? ChatStateMachine::default($key);
    }
}
