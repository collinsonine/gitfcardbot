<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChatDirection;
use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebhookController extends Controller
{
    public static function cleanPhone(string $phone): string
    {
        return preg_replace('/@(c\.us|lid|g\.us)$/', '', $phone);
    }

    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'has_media' => 'boolean',
            'media_path' => 'nullable|string',
        ]);

        $whatsappId = $validated['phone'];
        $phone = self::cleanPhone($whatsappId);
        $messageBody = $validated['message'] ?? '';
        $hasMedia = $validated['has_media'] ?? false;
        $mediaPath = $validated['media_path'] ?? null;

        if ($hasMedia && $mediaPath && file_exists($mediaPath)) {
            $storedPath = 'trade-media/'.basename($mediaPath);
            Storage::disk('public')->put($storedPath, file_get_contents($mediaPath));
            $mediaPath = $storedPath;
        }

        $user = User::firstOrCreate(
            ['phone_number' => $phone],
            [
                'name' => $validated['name'] ?? $phone,
                'whatsapp_id' => $whatsappId,
                'email' => null,
            ],
        );

        if (! $user->whatsapp_id) {
            $user->update(['whatsapp_id' => $whatsappId]);
        }

        $this->logInbound($user, $messageBody, $hasMedia, $mediaPath);

        return response()->json(['handled' => true]);
    }

    private function logInbound(User $user, string $message, bool $hasMedia, ?string $mediaPath): void
    {
        ChatLog::create([
            'user_id' => $user->id,
            'direction' => ChatDirection::Inbound,
            'message_body' => $message,
            'has_media' => $hasMedia,
            'media_path' => $mediaPath,
        ]);
    }
}
