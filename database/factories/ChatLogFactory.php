<?php

namespace Database\Factories;

use App\Models\ChatLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatLog>
 */
class ChatLogFactory extends Factory
{
    protected $model = ChatLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'message_body' => fake()->sentence(),
            'has_media' => false,
            'media_path' => null,
        ];
    }
}
