<?php

namespace App\StateMachines;

use App\Enums\ChatState;
use App\Enums\TradeStatus;
use App\Models\BotResponse;
use App\Models\Rate;
use App\Models\User;

class ChatStateMachine
{
    private const int MAX_INVALID_OPTIONS = 3;

    private const array VALID_CARD_TYPES = [
        '1' => 'Amazon',
        '2' => 'Apple',
        '3' => 'Google Play',
        '4' => 'Steam',
        '5' => 'eBay',
    ];

    private const array DEFAULTS = [
        'welcome' => "Welcome to Gift Card Trading!\n\nType 'start' to begin a new trade or 'help' for available commands.",
        'card_type_prompt' => "Please select your gift card type:\n\n1. Amazon\n2. Apple\n3. Google Play\n4. Steam\n5. eBay\n\nReply with the number of your choice.",
        'help_message' => "Welcome to Gift Card Trading!\n\nAvailable commands:\n- Type 'start' to begin a new trade\n- Type 'rates' to see current rates\n- Type 'help' to see this message",
        'invalid_card_type' => 'Please select a valid option (1-5) for your gift card type.',
        'invalid_amount' => 'Please enter a valid numeric amount in USD.',
        'invalid_general' => "I didn't understand that. Please type 'start' to begin a new trade or 'help' for assistance.",
        'three_strikes' => "I'm having trouble understanding. A human agent has been notified and will assist you shortly.\n\nIf you'd like to try again, type 'start'.",
        'rates_header' => 'Current Gift Card Rates:',
        'no_rates' => 'Current rates are being updated. Please check back shortly.',
        'confirm_prompt' => "Do you confirm this trade?\n\nReply 'yes' to confirm or 'no' to cancel.",
        'send_media' => "Please send a photo of your gift card and payment receipt.\n\nSend the images now.",
        'media_received' => "Your gift card and receipt have been received. Your trade (#:id) has been logged and is pending admin review.\n\nAn admin will review it shortly.",
        'send_bank_details' => "Your trade has been approved! 🎉\n\nPlease reply with your bank account details for payout:\n\nBank Name:\nAccount Number:\nAccount Name:",
        'bank_details_received' => 'Your bank details have been saved. The admin will process your payout shortly.',
        'payout_completed' => 'Your payout of :amount has been completed! 🎉',
        'trade_cancelled' => "Trade cancelled. Type 'start' to begin a new trade.",
    ];

    public function __construct(
        private User $user,
    ) {}

    public static function default(string $key): string
    {
        return self::DEFAULTS[$key] ?? '';
    }

    private const array CANCEL_KEYWORDS = ['cancel', 'stop', 'quit', 'exit'];

    public function process(string $message, bool $hasMedia = false, ?string $mediaPath = null): ?string
    {
        $normalizedMessage = strtolower(trim($message));

        if ($this->user->is_bot_paused) {
            if (in_array($normalizedMessage, ['start', 'hi', 'hello', 'begin', 'trade', 'help'], true)) {
                $this->user->is_bot_paused = false;
                $this->user->invalid_option_count = 0;
                $this->user->save();

                return $this->handleIdle($message);
            }

            return null;
        }

        if (in_array($normalizedMessage, self::CANCEL_KEYWORDS, true)) {
            return $this->cancelTrade();
        }

        $state = $this->user->chat_state;

        $response = match ($state) {
            ChatState::Idle => $this->handleIdle($message),
            ChatState::AwaitingCardType => $this->handleAwaitingCardType($message),
            ChatState::AwaitingAmount => $this->handleAwaitingAmount($message),
            ChatState::AwaitingConfirmation => $this->handleAwaitingConfirmation($message),
            ChatState::AwaitingMedia => $this->handleAwaitingMedia($message, $hasMedia, $mediaPath),
            ChatState::AwaitingBankDetails => $this->handleAwaitingBankDetails($message),
            ChatState::LoggedPending => $this->handleLoggedPending($message),
            default => BotResponse::text('welcome'),
        };

        if ($response === null) {
            $this->user->increment('invalid_option_count');

            if ($this->user->invalid_option_count >= self::MAX_INVALID_OPTIONS) {
                $this->user->is_bot_paused = true;
                $this->user->save();

                return BotResponse::text('three_strikes');
            }

            $this->user->save();

            return match ($state) {
                ChatState::AwaitingCardType => BotResponse::text('invalid_card_type'),
                ChatState::AwaitingAmount => BotResponse::text('invalid_amount'),
                ChatState::AwaitingConfirmation => "Please reply 'yes' to confirm or 'no' to cancel.",
                ChatState::AwaitingMedia => 'Please send a photo of your gift card and receipt.',
                ChatState::AwaitingBankDetails => 'Please send your bank account details (Bank Name, Account Number, Account Name).',
                default => BotResponse::text('invalid_general'),
            };
        }

        $this->user->invalid_option_count = 0;
        $this->user->save();

        return $response;
    }

    private function handleIdle(string $message): ?string
    {
        $normalized = strtolower(trim($message));

        if (in_array($normalized, ['start', 'hi', 'hello', 'begin', 'trade'], true)) {
            $this->user->chat_state = ChatState::AwaitingCardType;
            $this->user->save();

            return BotResponse::text('card_type_prompt');
        }

        if (in_array($normalized, ['help', 'support'], true)) {
            return BotResponse::text('help_message');
        }

        if ($normalized === 'rates') {
            return $this->formatRates();
        }

        return null;
    }

    private function handleAwaitingCardType(string $message): ?string
    {
        $message = trim($message);

        if (isset(self::VALID_CARD_TYPES[$message])) {
            $this->user->update(['trade_draft' => array_merge($this->user->trade_draft ?? [], [
                'card_type' => self::VALID_CARD_TYPES[$message],
            ])]);

            $this->user->chat_state = ChatState::AwaitingAmount;
            $this->user->save();

            $rate = Rate::where('card_name', self::VALID_CARD_TYPES[$message])->first();

            $rateInfo = '';
            if ($rate) {
                $rateInfo = "\n\nCurrent rates for ".self::VALID_CARD_TYPES[$message].":\nUSD: ₦{$rate->usd_ngn}\nGBP: ₦{$rate->gbp_ngn}\nEUR: ₦{$rate->eur_ngn}";
            }

            return 'You selected: '.self::VALID_CARD_TYPES[$message]."{$rateInfo}\n\nPlease enter the gift card amount in USD:";
        }

        return null;
    }

    private function handleAwaitingAmount(string $message): ?string
    {
        $amount = str_replace(['$', ',', ' '], '', trim($message));

        if (! is_numeric($amount) || (float) $amount <= 0) {
            return null;
        }

        $amountUsd = (float) $amount;
        $draft = $this->user->trade_draft ?? [];
        $cardType = $draft['card_type'] ?? 'Amazon';

        $rate = Rate::where('card_name', $cardType)->first();
        $ratePaid = (float) ($rate?->usd_ngn ?? 0.85);

        $customerPayout = round($amountUsd * $ratePaid, 2);
        $estimatedProfit = round($amountUsd - $customerPayout, 2);

        $this->user->update(['trade_draft' => array_merge($draft, [
            'amount_usd' => $amountUsd,
            'rate_paid' => $ratePaid,
            'customer_payout' => $customerPayout,
            'estimated_profit' => $estimatedProfit,
        ])]);

        $this->user->chat_state = ChatState::AwaitingConfirmation;
        $this->user->save();

        return "Trade Summary:\n\nCard Type: {$cardType}\nAmount: \${$amountUsd}\nRate: {$ratePaid}\nYour Payout: \${$customerPayout}\n\n".BotResponse::text('confirm_prompt');
    }

    private function handleAwaitingConfirmation(string $message): ?string
    {
        $normalized = strtolower(trim($message));

        if (in_array($normalized, ['yes', 'confirm', 'y'], true)) {
            $draft = $this->user->trade_draft ?? [];

            $trade = $this->user->trades()->create([
                'card_type' => $draft['card_type'] ?? 'Amazon',
                'amount_usd' => $draft['amount_usd'] ?? 0,
                'rate_paid' => (float) ($draft['rate_paid'] ?? 0.85),
                'customer_payout' => $draft['customer_payout'] ?? 0,
                'estimated_profit' => $draft['estimated_profit'] ?? 0,
                'status' => TradeStatus::AwaitingMedia,
            ]);

            $this->user->chat_state = ChatState::AwaitingMedia;
            $this->user->save();

            return "Trade #{$trade->id} has been logged!\n\n".BotResponse::text('send_media');
        }

        if (in_array($normalized, ['no', 'cancel', 'n'], true)) {
            $this->user->update(['trade_draft' => null]);
            $this->user->chat_state = ChatState::Idle;
            $this->user->save();

            return BotResponse::text('trade_cancelled');
        }

        return null;
    }

    private function handleAwaitingMedia(string $message, bool $hasMedia, ?string $mediaPath): ?string
    {
        $trade = $this->user->trades()
            ->where('status', TradeStatus::AwaitingMedia)
            ->latest()
            ->first();

        if (! $trade) {
            return null;
        }

        if ($hasMedia && $mediaPath) {
            $paths = array_merge($trade->media_paths ?? [], [$mediaPath]);
            $trade->update(['media_paths' => $paths]);
        }

        if (! $hasMedia) {
            return null;
        }

        $trade->update(['status' => TradeStatus::Pending]);

        $this->user->update(['trade_draft' => null]);
        $this->user->chat_state = ChatState::Idle;
        $this->user->save();

        $template = BotResponse::text('media_received');

        return str_replace(':id', (string) $trade->id, $template);
    }

    private function handleAwaitingBankDetails(string $message): ?string
    {
        $trade = $this->user->trades()
            ->where('status', TradeStatus::Approved)
            ->latest()
            ->first();

        if (! $trade) {
            return null;
        }

        $trade->update([
            'bank_details' => $message,
            'status' => TradeStatus::AwaitingBankDetails,
        ]);

        $this->user->chat_state = ChatState::Idle;
        $this->user->save();

        return BotResponse::text('bank_details_received');
    }

    private function handleLoggedPending(string $message): ?string
    {
        $normalized = strtolower(trim($message));

        if (in_array($normalized, ['start', 'begin', 'new', 'yes'], true)) {
            $this->user->chat_state = ChatState::AwaitingCardType;
            $this->user->save();

            return BotResponse::text('card_type_prompt');
        }

        if ($normalized === 'rates') {
            return $this->formatRates();
        }

        return null;
    }

    private function cancelTrade(): string
    {
        if ($this->user->chat_state === ChatState::AwaitingMedia) {
            $this->user->trades()
                ->where('status', TradeStatus::AwaitingMedia)
                ->latest()
                ->first()
                ?->update(['status' => TradeStatus::Declined]);
        }

        $this->user->update(['trade_draft' => null]);
        $this->user->chat_state = ChatState::Idle;
        $this->user->save();

        return BotResponse::text('trade_cancelled');
    }

    private function formatRates(): string
    {
        $rates = Rate::all();

        if ($rates->isEmpty()) {
            return BotResponse::text('no_rates');
        }

        $output = BotResponse::text('rates_header')."\n\n";
        foreach ($rates as $rate) {
            $output .= "{$rate->card_name}: USD ₦{$rate->usd_ngn} | GBP ₦{$rate->gbp_ngn} | EUR ₦{$rate->eur_ngn}\n";
        }

        return $output;
    }
}
