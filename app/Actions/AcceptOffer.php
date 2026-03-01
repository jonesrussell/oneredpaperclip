<?php

namespace App\Actions;

use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Offer;
use App\Models\Trade;
use App\Notifications\OfferAcceptedNotification;
use App\Services\XpService;
use Illuminate\Support\Facades\DB;

class AcceptOffer
{
    public function __construct(
        private XpService $xpService
    ) {}

    /**
     * Accept an offer: create a trade, update offer status, notify offerer, award XP.
     */
    public function __invoke(Offer $offer): Trade
    {
        $trade = DB::transaction(function () use ($offer) {
            $challenge = $offer->challenge;

            $trade = Trade::create([
                'challenge_id' => $challenge->id,
                'offer_id' => $offer->id,
                'position' => $challenge->trades()->lockForUpdate()->count() + 1,
                'offered_item_id' => $offer->offered_item_id,
                'received_item_id' => $challenge->current_item_id,
                'status' => TradeStatus::PendingConfirmation,
            ]);

            $offer->update(['status' => OfferStatus::Accepted]);

            if ($challenge->user) {
                $this->xpService->awardOfferReceived($challenge->user);
            }

            return $trade;
        });

        $offer->load(['fromUser', 'challenge']);
        $trade->load(['challenge', 'offeredItem']);

        if ($offer->fromUser) {
            try {
                $offer->fromUser->notify(new OfferAcceptedNotification($offer, $trade));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $trade;
    }
}
