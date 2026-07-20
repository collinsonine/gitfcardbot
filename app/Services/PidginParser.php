<?php

namespace App\Services;

use App\Models\CardAlias;
use App\Models\User;
use Illuminate\Support\Collection;

class PidginParser
{
    private const KNOWN_CARDS = [
        'amazon',
        'apple',
        'google play',
        'google',
        'steam',
        'ebay',
    ];

    private const ALIAS_MAP = [
        'amazon' => 'Amazon',
        'apple' => 'Apple',
        'google play' => 'Google Play',
        'google' => 'Google Play',
        'steam' => 'Steam',
        'ebay' => 'eBay',
        'amazn' => 'Amazon',
        'amazone' => 'Amazon',
        'apl' => 'Apple',
        'appel' => 'Apple',
        'steeam' => 'Steam',
        'steam' => 'Steam',
        'steem' => 'Steam',
        'ebey' => 'eBay',
        'eby' => 'eBay',
        'googl' => 'Google Play',
        'gp' => 'Google Play',
        'gplay' => 'Google Play',
    ];

    private ?Collection $dynamicAliases = null;

    /**
     * Parse a message and return trade data, merging with any pending context from previous messages.
     *
     * Returns an array with keys:
     *  - card_type: string (if resolved)
     *  - amount_usd: float (if resolved)
     *  - pending_card_type: string|null (card type found but no amount yet)
     *  - pending_amount: float|null (amount found but no card type yet)
     *  - complete: bool (both card_type and amount_usd present)
     */
    public function parse(string $message, ?User $user = null): ?array
    {
        $normalised = $this->normalise($message);

        $amount = $this->extractAmount($normalised);
        $cardType = $this->extractCardType($normalised);

        // Check if message is just a greeting/filler with no trade info
        $isFiller = $this->isFillerMessage($normalised);

        // Filler with no new info clears pending context
        if ($isFiller && $cardType === null && $amount === null) {
            return null;
        }

        // Merge with pending context from previous messages
        $pendingCard = $user?->pending_card_type;
        $pendingAmount = $user?->pending_amount;

        // Resolve card type: current message > pending context > habit
        $resolvedCard = $cardType ?? $pendingCard;

        // Apply habit profiling if still no card type
        if ($resolvedCard === null && $user !== null) {
            $habitCard = $user->dominantCardType();
            if ($habitCard !== null && $amount !== null) {
                $resolvedCard = $habitCard;
            }
        }

        // Resolve amount: current message > pending context
        $resolvedAmount = $amount ?? $pendingAmount;

        // Determine what to save as pending and what to return
        $pendingCardSave = null;
        $pendingAmountSave = null;

        if ($resolvedCard !== null && $resolvedAmount !== null) {
            // Complete — clear pending context
            $pendingCardSave = null;
            $pendingAmountSave = null;
        } elseif ($resolvedCard !== null) {
            // Have card, waiting for amount
            $pendingCardSave = $resolvedCard;
            $pendingAmountSave = null;
        } elseif ($resolvedAmount !== null) {
            // Have amount, waiting for card
            $pendingCardSave = null;
            $pendingAmountSave = $resolvedAmount;
        } elseif ($pendingCard !== null || $pendingAmount !== null) {
            // No new info, keep existing pending context
            $pendingCardSave = $pendingCard;
            $pendingAmountSave = $pendingAmount;
        } else {
            return null;
        }

        $complete = $resolvedCard !== null && $resolvedAmount !== null;

        return [
            'card_type' => $resolvedCard,
            'amount_usd' => $resolvedAmount !== null ? round($resolvedAmount, 2) : null,
            'pending_card_type' => $pendingCardSave,
            'pending_amount' => $pendingAmountSave,
            'complete' => $complete,
        ];
    }

    public function learn(string $aliasWord, string $resolvedCard): CardAlias
    {
        $existing = CardAlias::where('alias_word', $aliasWord)->first();

        if ($existing) {
            $existing->incrementHit();

            return $existing;
        }

        return CardAlias::create([
            'alias_word' => $aliasWord,
            'resolved_card' => $resolvedCard,
            'hit_count' => 1,
        ]);
    }

    private function isFillerMessage(string $normalised): bool
    {
        $fillers = [
            'ok', 'okay', 'yes', 'yep', 'yeah', 'no', 'nope',
            'hi', 'hello', 'hey', 'thanks', 'thank you', 'pls', 'please',
            'start', 'stop', 'cancel', 'help',
        ];

        $words = array_values(array_filter(explode(' ', $normalised)));

        if (count($words) === 0) {
            return true;
        }

        // Single word that's a filler
        if (count($words) === 1) {
            return in_array($words[0], $fillers, true);
        }

        // All words are fillers
        return count(array_diff($words, $fillers)) === 0;
    }

    private function normalise(string $message): string
    {
        $lower = mb_strtolower(trim($message));

        $lower = preg_replace('/[^\p{L}\p{N}\s.]/u', ' ', $lower);

        return preg_replace('/\s+/', ' ', $lower);
    }

    private function extractAmount(string $normalised): ?float
    {
        $patterns = [
            '/(\d[\d,]*\.?\d*)\s*(?:usd|dollars?|\$)/i',
            '/(?:amount|value|face|load|worth)\s*[:=]?\s*(\d[\d,]*\.?\d*)/i',
            '/(\d[\d,]*\.?\d*)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalised, $matches)) {
                $raw = str_replace(',', '', $matches[1]);
                $value = (float) $raw;

                if ($value > 0 && $value < 1_000_000) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function extractCardType(string $normalised): ?string
    {
        $knownPatterns = [
            '/\bamaz[on]*\b/i',
            '/\bapple\b/i',
            '/\bgoogle\s*play\b/i',
            '/\bgplay\b/i',
            '/\bgp\b/i',
            '/\bsteam\b/i',
            '/\bsteeam\b/i',
            '/\bsteem\b/i',
            '/\bebay\b/i',
            '/\bebey\b/i',
            '/\beby\b/i',
        ];

        $knownMap = [
            'amazon' => 'Amazon',
            'apple' => 'Apple',
            'google play' => 'Google Play',
            'gplay' => 'Google Play',
            'gp' => 'Google Play',
            'steam' => 'Steam',
            'steeam' => 'Steam',
            'steem' => 'Steam',
            'ebay' => 'eBay',
            'ebey' => 'eBay',
            'eby' => 'eBay',
        ];

        foreach ($knownPatterns as $index => $pattern) {
            if (preg_match($pattern, $normalised, $m)) {
                $matched = strtolower($m[0]);
                $keys = array_keys($knownMap);

                return $knownMap[$keys[$index]] ?? ucfirst($matched);
            }
        }

        $dynamicAliases = $this->getDynamicAliases();

        foreach ($dynamicAliases as $alias) {
            $escaped = preg_quote($alias->alias_word, '/');
            if (preg_match('/\b'.$escaped.'\b/i', $normalised)) {
                $alias->incrementHit();

                return $alias->resolved_card;
            }
        }

        $words = explode(' ', $normalised);

        foreach ($words as $word) {
            if (strlen($word) < 3) {
                continue;
            }

            $similar = $this->findSimilarAlias($word);

            if ($similar !== null) {
                return $similar;
            }
        }

        return null;
    }

    private function getDynamicAliases(): Collection
    {
        if ($this->dynamicAliases === null) {
            $this->dynamicAliases = CardAlias::orderByDesc('hit_count')->get();
        }

        return $this->dynamicAliases;
    }

    private function findSimilarAlias(string $word): ?string
    {
        $aliases = $this->getDynamicAliases();

        foreach ($aliases as $alias) {
            if (levenshtein($word, $alias->alias_word) <= 2 && strlen($word) >= 3) {
                $alias->incrementHit();

                return $alias->resolved_card;
            }
        }

        return null;
    }
}
