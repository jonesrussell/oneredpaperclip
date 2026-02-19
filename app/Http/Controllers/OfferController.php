<?php

namespace App\Http\Controllers;

use App\Actions\AcceptOffer;
use App\Actions\CreateOffer;
use App\Actions\DeclineOffer;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Campaign;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;

class OfferController extends Controller
{
    /**
     * Store a newly created offer for a campaign.
     */
    public function store(StoreOfferRequest $request, Campaign $campaign, CreateOffer $createOffer): RedirectResponse
    {
        $offer = $createOffer($request->validated(), $campaign, $request->user());

        return redirect()->route('campaigns.show', $campaign);
    }

    /**
     * Accept an offer (campaign owner). Creates a trade and notifies the offerer.
     */
    public function accept(Offer $offer, AcceptOffer $acceptOffer): RedirectResponse
    {
        $this->authorize('accept', $offer);

        $acceptOffer($offer);

        return redirect()->route('campaigns.show', $offer->campaign);
    }

    /**
     * Decline an offer (campaign owner). Notifies the offerer.
     */
    public function decline(Offer $offer, DeclineOffer $declineOffer): RedirectResponse
    {
        $this->authorize('decline', $offer);

        $declineOffer($offer);

        return redirect()->route('campaigns.show', $offer->campaign);
    }
}
