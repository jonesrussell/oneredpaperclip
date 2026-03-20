<?php

namespace App\Actions;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Notifications\OfferDeclinedNotification;
use Illuminate\Support\Facades\DB;

class DeclineOffer
{
    /**
     * Decline an offer and notify the offerer.
     */
    public function __invoke(Offer $offer): Offer
    {
        $offer = DB::transaction(function () use ($offer) {
            $offer = Offer::lockForUpdate()->findOrFail($offer->id);

            if ($offer->status !== OfferStatus::Pending) {
                throw new \RuntimeException('Offer is no longer pending.');
            }

            $offer->update(['status' => OfferStatus::Declined]);

            return $offer;
        });

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
