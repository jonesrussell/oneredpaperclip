<?php

namespace App\Enums;

enum TradeStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Completed = 'completed';
    case Disputed = 'disputed';
}
