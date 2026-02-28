<?php

namespace App\Http\Controllers\Api;

use App\Actions\AcceptOffer;
use App\Actions\CreateOffer;
use App\Actions\DeclineOffer;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Challenge;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;

class OfferApiController extends Controller
{
    /**
     * Store a new offer on a challenge (authenticated).
     */
    public function store(StoreOfferRequest $request, Challenge $challenge, CreateOffer $createOffer): JsonResponse
    {
        $offer = $createOffer($request->validated(), $challenge, $request->user());

        return response()->json([
            'offer' => ['id' => $offer->id, 'status' => $offer->status->value],
            'message' => 'Offer submitted.',
        ], 201);
    }

    /**
     * Accept an offer (challenge owner). Authenticated; authorization in controller.
     */
    public function accept(Offer $offer, AcceptOffer $acceptOffer): JsonResponse
    {
        $this->authorize('accept', $offer);

        $acceptOffer($offer);

        return response()->json([
            'message' => 'Offer accepted.',
            'trade_id' => $offer->trade->id,
        ]);
    }

    /**
     * Decline an offer (challenge owner).
     */
    public function decline(Offer $offer, DeclineOffer $declineOffer): JsonResponse
    {
        $this->authorize('decline', $offer);

        $declineOffer($offer);

        return response()->json(['message' => 'Offer declined.']);
    }
}
