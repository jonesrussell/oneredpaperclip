<?php

namespace App\Actions;

use App\Enums\ChallengeStatus;
use App\Enums\TradeStatus;
use App\Models\Follow;
use App\Models\Trade;
use App\Models\User;
use App\Notifications\ChallengeCompletedNotification;
use App\Notifications\TradeCompletedNotification;
use App\Notifications\TradePendingConfirmationNotification;
use App\Services\XpService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConfirmTrade
{
    public function __construct(
        private XpService $xpService
    ) {}

    /**
     * Record confirmation from offerer or challenge owner. Owner confirmation
     * completes the trade immediately and advances challenge current_item_id.
     */
    public function __invoke(Trade $trade, User $user): Trade
    {
        [$trade, $wasAlreadyCompleted, $previouslyConfirmedByOfferer] = DB::transaction(function () use ($trade, $user) {
            $trade = Trade::lockForUpdate()->findOrFail($trade->id);
            $trade->load(['offer', 'challenge']);

            $wasAlreadyCompleted = $trade->status === TradeStatus::Completed;
            $previouslyConfirmedByOfferer = $trade->confirmed_by_offerer_at !== null;

            $isOfferer = $trade->offer->from_user_id === $user->id;
            $isOwner = $trade->challenge->user_id === $user->id;

            if ($isOfferer && $trade->confirmed_by_offerer_at === null) {
                $trade->update(['confirmed_by_offerer_at' => Carbon::now()]);
            }

            if ($isOwner && $trade->confirmed_by_owner_at === null) {
                $trade->update([
                    'confirmed_by_owner_at' => Carbon::now(),
                    'confirmed_by_offerer_at' => $trade->confirmed_by_offerer_at ?? Carbon::now(),
                ]);
            }

            $trade->refresh();

            if ($trade->confirmed_by_owner_at !== null) {
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

            return [$trade->fresh(), $wasAlreadyCompleted, $previouslyConfirmedByOfferer];
        });

        $trade->load(['offer.fromUser', 'challenge.user', 'challenge.goalItem', 'offeredItem']);

        $offerer = $trade->offer->fromUser;
        $owner = $trade->challenge->user;
        $isNowCompleted = $trade->status === TradeStatus::Completed;
        $nowConfirmedByOfferer = $trade->confirmed_by_offerer_at !== null;

        if ($isNowCompleted && ! $wasAlreadyCompleted) {
            if ($offerer) {
                $offerer->notify(new TradeCompletedNotification($trade));
            }
            if ($owner) {
                $owner->notify(new TradeCompletedNotification($trade));
            }

            if ($trade->challenge->status === ChallengeStatus::Completed) {
                $this->notifyChallengeCompleted($trade);
            }
        } elseif (! $isNowCompleted) {
            if ($nowConfirmedByOfferer && ! $previouslyConfirmedByOfferer && $owner) {
                $owner->notify(new TradePendingConfirmationNotification($trade, $offerer));
            }
        }

        return $trade;
    }

    /**
     * Notify challenge owner and followers when a challenge is completed.
     */
    private function notifyChallengeCompleted(Trade $trade): void
    {
        $challenge = $trade->challenge;

        if ($challenge->user) {
            $challenge->user->notify(new ChallengeCompletedNotification($challenge));
        }

        $followerIds = Follow::query()
            ->where('followable_type', $challenge->getMorphClass())
            ->where('followable_id', $challenge->id)
            ->where('user_id', '!=', $challenge->user_id)
            ->pluck('user_id');

        $followers = User::whereIn('id', $followerIds)->get();
        foreach ($followers as $follower) {
            $follower->notify(new ChallengeCompletedNotification($challenge));
        }
    }
}
