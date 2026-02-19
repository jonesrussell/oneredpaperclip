<?php

namespace App\Actions;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;

class ConfirmTrade
{
    /**
     * Record confirmation from offerer or campaign owner. When both have confirmed,
     * mark trade completed and advance campaign current_item_id to the offered item.
     */
    public function __invoke(Trade $trade, User $user): Trade
    {
        $trade->load(['offer', 'campaign']);

        $isOfferer = $trade->offer->from_user_id === $user->id;
        $isOwner = $trade->campaign->user_id === $user->id;

        if ($isOfferer && $trade->confirmed_by_offerer_at === null) {
            $trade->update(['confirmed_by_offerer_at' => Carbon::now()]);
        }

        if ($isOwner && $trade->confirmed_by_owner_at === null) {
            $trade->update(['confirmed_by_owner_at' => Carbon::now()]);
        }

        $trade->refresh();

        if ($trade->confirmed_by_offerer_at !== null && $trade->confirmed_by_owner_at !== null) {
            $trade->update(['status' => TradeStatus::Completed]);
            $trade->campaign->update(['current_item_id' => $trade->offered_item_id]);
        }

        return $trade->fresh();
    }
}
