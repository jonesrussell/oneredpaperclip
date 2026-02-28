<?php

namespace App\Actions;

use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\Trade;
use App\Services\XpService;

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
        $challenge = $offer->challenge;

        $trade = Trade::create([
            'challenge_id' => $challenge->id,
            'offer_id' => $offer->id,
            'position' => $challenge->trades()->count() + 1,
            'offered_item_id' => $offer->offered_item_id,
            'received_item_id' => $challenge->current_item_id,
            'status' => TradeStatus::PendingConfirmation,
        ]);

        $offer->update(['status' => OfferStatus::Accepted]);

        Notification::create([
            'user_id' => $offer->from_user_id,
            'type' => 'offer_accepted',
            'data' => [
                'challenge_id' => $challenge->id,
                'offer_id' => $offer->id,
            ],
            'read_at' => null,
        ]);

        if ($challenge->user) {
            $this->xpService->awardOfferReceived($challenge->user);
        }

        return $trade;
    }
}
