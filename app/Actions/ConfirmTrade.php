<?php

namespace App\Actions;

use App\Enums\ChallengeStatus;
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
     * Record confirmation from offerer or challenge owner. When both have confirmed,
     * mark trade completed and advance challenge current_item_id to the offered item.
     */
    public function __invoke(Trade $trade, User $user): Trade
    {
        $trade->load(['offer', 'challenge']);

        $isOfferer = $trade->offer->from_user_id === $user->id;
        $isOwner = $trade->challenge->user_id === $user->id;

        if ($isOfferer && $trade->confirmed_by_offerer_at === null) {
            $trade->update(['confirmed_by_offerer_at' => Carbon::now()]);
        }

        if ($isOwner && $trade->confirmed_by_owner_at === null) {
            $trade->update(['confirmed_by_owner_at' => Carbon::now()]);
        }

        $trade->refresh();

        if ($trade->confirmed_by_offerer_at !== null && $trade->confirmed_by_owner_at !== null) {
            $trade->update(['status' => TradeStatus::Completed]);
            $trade->challenge->update(['current_item_id' => $trade->offered_item_id]);
            $trade->challenge->increment('trades_count');

            $offerer = $trade->offer->fromUser;
            $owner = $trade->challenge->user;

            if ($offerer) {
                $this->xpService->awardTradeCompletion($offerer);
            }
            if ($owner) {
                $this->xpService->awardTradeCompletion($owner);
            }

            if ($trade->offered_item_id === $trade->challenge->goal_item_id) {
                $trade->challenge->update(['status' => ChallengeStatus::Completed]);
                if ($owner) {
                    $this->xpService->awardChallengeCompletion($owner);
                }
            }
        }

        return $trade->fresh();
    }
}
