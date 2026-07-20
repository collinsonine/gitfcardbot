<?php

namespace App\Services;

use App\Enums\TradeStatus;
use App\Models\Rate;
use App\Models\Trade;
use App\Models\User;

class TradeDraftService
{
    public function __construct(
        private PidginParser $parser,
    ) {}

    public function parseAndDraft(string $message, User $user): ?Trade
    {
        $parsed = $this->parser->parse($message, $user);

        // Filler/greeting with no trade info — clear any pending context
        if ($parsed === null) {
            if ($user->pending_card_type !== null || $user->pending_amount !== null) {
                $user->update([
                    'pending_card_type' => null,
                    'pending_amount' => null,
                    'pending_context_at' => null,
                ]);
            }

            return null;
        }

        // Update pending context on the user
        $user->update([
            'pending_card_type' => $parsed['pending_card_type'],
            'pending_amount' => $parsed['pending_amount'],
            'pending_context_at' => now(),
        ]);

        // If not complete yet (waiting for the other half), no draft yet
        if (! $parsed['complete']) {
            return null;
        }

        $rate = Rate::where('card_name', $parsed['card_type'])->first();

        if ($rate === null) {
            return null;
        }

        $amountUsd = round($parsed['amount_usd'], 2);
        $ratePaid = (float) $rate->usd_ngn;
        $customerPayout = round($amountUsd * $ratePaid, 2);
        $estimatedProfit = round($amountUsd - $customerPayout, 2);

        // Clear pending context once draft is created
        $user->update([
            'pending_card_type' => null,
            'pending_amount' => null,
            'pending_context_at' => null,
        ]);

        return Trade::create([
            'user_id' => $user->id,
            'card_type' => $parsed['card_type'],
            'amount_usd' => $amountUsd,
            'rate_paid' => $ratePaid,
            'customer_payout' => $customerPayout,
            'estimated_profit' => $estimatedProfit,
            'status' => TradeStatus::Draft,
            'source' => 'shadow_parser',
            'source_message' => $message,
        ]);
    }

    public function confirmDraft(Trade $trade): Trade
    {
        $trade->update(['status' => TradeStatus::Pending]);

        return $trade->fresh();
    }

    public function editAndConfirmDraft(Trade $trade, array $data, ?string $newAlias = null): Trade
    {
        if ($newAlias !== null && $trade->card_type !== null) {
            $this->parser->learn($newAlias, $trade->card_type);
        }

        $ratePaid = $data['rate_paid'] ?? $trade->rate_paid;
        $amountUsd = $data['amount_usd'] ?? $trade->amount_usd;
        $cardType = $data['card_type'] ?? $trade->card_type;

        $customerPayout = round((float) $amountUsd * (float) $ratePaid, 2);
        $estimatedProfit = round((float) $amountUsd - $customerPayout, 2);

        $trade->update([
            'card_type' => $cardType,
            'amount_usd' => round((float) $amountUsd, 2),
            'rate_paid' => round((float) $ratePaid, 2),
            'customer_payout' => $customerPayout,
            'estimated_profit' => $estimatedProfit,
            'status' => TradeStatus::Approved,
            'source' => 'shadow_manual',
        ]);

        return $trade->fresh();
    }

    public function learnFromCorrection(string $originalMessage, string $resolvedCard): void
    {
        $normalised = mb_strtolower(trim($originalMessage));
        $words = preg_split('/\s+/', $normalised);

        foreach ($words as $word) {
            $word = preg_replace('/[^\p{L}\p{N}]/u', '', $word);

            if (strlen($word) < 3) {
                continue;
            }

            $isKnown = in_array(strtolower($word), [
                'i', 'get', 'have', 'want', 'sell', 'buy', 'load', 'card',
                'usd', 'dollar', 'dollars', 'rate', 'how', 'much', 'be',
                'the', 'a', 'an', 'my', 'your', 'for', 'with', 'and',
                'ready', 'amount', 'value', 'face', 'worth', 'this', 'that',
            ]);

            if (! $isKnown) {
                $this->parser->learn($word, $resolvedCard);
            }
        }
    }
}
