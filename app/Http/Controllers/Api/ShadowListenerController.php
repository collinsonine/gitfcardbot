<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChatDirection;
use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Models\User;
use App\Services\TradeDraftService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShadowListenerController extends Controller
{
    public function __construct(
        private TradeDraftService $draftService,
        private WhatsAppService $whatsApp,
    ) {}

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
        $phone = WebhookController::cleanPhone($whatsappId);
        $messageBody = $validated['message'] ?? '';

        if (empty(trim($messageBody))) {
            return response()->json(['handled' => false, 'reason' => 'empty_message']);
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

        $this->logInbound($user, $messageBody);

        $draft = $this->draftService->parseAndDraft($messageBody, $user);

        if ($draft === null) {
            return response()->json([
                'handled' => true,
                'draft_created' => false,
                'reason' => 'no_parseable_trade',
            ]);
        }

        return response()->json([
            'handled' => true,
            'draft_created' => true,
            'trade_id' => $draft->id,
            'card_type' => $draft->card_type,
            'amount_usd' => $draft->amount_usd,
        ]);
    }

    private function logInbound(User $user, string $message): void
    {
        ChatLog::create([
            'user_id' => $user->id,
            'direction' => ChatDirection::Inbound,
            'message_body' => $message,
        ]);
    }
}
