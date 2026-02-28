<?php

namespace App\Http\Controllers;

use App\Actions\AcceptOffer;
use App\Actions\CreateOffer;
use App\Actions\DeclineOffer;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Challenge;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;

class OfferController extends Controller
{
    /**
     * Store a newly created offer for a challenge.
     */
    public function store(StoreOfferRequest $request, Challenge $challenge, CreateOffer $createOffer): RedirectResponse
    {
        $offer = $createOffer($request->validated(), $challenge, $request->user());

        return redirect()->route('challenges.show', $challenge);
    }

    /**
     * Accept an offer (challenge owner). Creates a trade and notifies the offerer.
     */
    public function accept(Offer $offer, AcceptOffer $acceptOffer): RedirectResponse
    {
        $this->authorize('accept', $offer);

        $acceptOffer($offer);

        return redirect()->route('challenges.show', $offer->challenge);
    }

    /**
     * Decline an offer (challenge owner). Notifies the offerer.
     */
    public function decline(Offer $offer, DeclineOffer $declineOffer): RedirectResponse
    {
        $this->authorize('decline', $offer);

        $declineOffer($offer);

        return redirect()->route('challenges.show', $offer->challenge);
    }
}
