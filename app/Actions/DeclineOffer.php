<?php

namespace App\Actions;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Notifications\OfferDeclinedNotification;

class DeclineOffer
{
    /**
     * Decline an offer and notify the offerer.
     */
    public function __invoke(Offer $offer): Offer
    {
        $offer->update(['status' => OfferStatus::Declined]);

        $offer->load(['fromUser', 'challenge']);

        if ($offer->fromUser) {
            try {
                $offer->fromUser->notify(new OfferDeclinedNotification($offer));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $offer->fresh();
    }
}
