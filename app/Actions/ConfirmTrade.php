<?php

namespace App\Actions;

use App\Enums\CampaignStatus;
use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use App\Services\XpService;
use Carbon\Carbon;

class ConfirmTrade
{
    public function __construct(
        private XpService $xpService
    ) {}

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
            $trade->campaign->increment('trades_count');

            $offerer = $trade->offer->fromUser;
            $owner = $trade->campaign->user;

            if ($offerer) {
                $this->xpService->awardTradeCompletion($offerer);
            }
            if ($owner) {
                $this->xpService->awardTradeCompletion($owner);
            }

            if ($trade->offered_item_id === $trade->campaign->goal_item_id) {
                $trade->campaign->update(['status' => CampaignStatus::Completed]);
                if ($owner) {
                    $this->xpService->awardCampaignCompletion($owner);
                }
            }
        }

        return $trade->fresh();
    }
}
