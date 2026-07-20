<?php

namespace App\Enums;

enum ChatState: string
{
    case Idle = 'idle';
    case AwaitingCardType = 'awaiting_card_type';
    case AwaitingAmount = 'awaiting_amount';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case AwaitingMedia = 'awaiting_media';
    case AwaitingBankDetails = 'awaiting_bank_details';
    case LoggedPending = 'logged/pending';
}
