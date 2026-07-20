<?php

namespace App\Enums;

enum TradeStatus: string
{
    case Pending = 'pending';
    case Draft = 'draft';
    case Approved = 'approved';
    case Adjusted = 'adjusted';
    case Manual = 'manual';
    case Declined = 'declined';
    case AwaitingMedia = 'awaiting_media';
    case AwaitingBankDetails = 'awaiting_bank_details';
    case Completed = 'completed';
}
