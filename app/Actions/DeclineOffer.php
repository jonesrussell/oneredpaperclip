<?php

namespace App\Actions;

use App\Enums\OfferStatus;
use App\Models\Notification;
use App\Models\Offer;

class DeclineOffer
{
    /**
     * Decline an offer and notify the offerer.
     */
    public function __invoke(Offer $offer): Offer
    {
        $offer->update(['status' => OfferStatus::Declined]);

        Notification::create([
            'user_id' => $offer->from_user_id,
            'type' => 'offer_declined',
            'data' => [
                'challenge_id' => $offer->challenge_id,
                'offer_id' => $offer->id,
            ],
            'read_at' => null,
        ]);

        return $offer->fresh();
    }
}
