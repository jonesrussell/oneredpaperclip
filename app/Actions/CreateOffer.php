<?php

namespace App\Actions;

use App\Enums\ItemRole;
use App\Enums\OfferStatus;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;

class CreateOffer
{
    /**
     * Create an offer: create the offered item, then the offer targeting the challenge's current item.
     *
     * @param  array{offered_item: array{title: string, description?: string|null}, message?: string|null}  $validated
     */
    public function __invoke(array $validated, Challenge $challenge, User $user): Offer
    {
        $currentItem = $challenge->currentItem;
        if (! $currentItem) {
            throw new \InvalidArgumentException('Challenge has no current item.');
        }

        $offeredItem = Item::create([
            'itemable_type' => User::class,
            'itemable_id' => $user->id,
            'role' => ItemRole::Offered->value,
            'title' => $validated['offered_item']['title'],
            'description' => $validated['offered_item']['description'] ?? null,
        ]);

        return Offer::create([
            'challenge_id' => $challenge->id,
            'from_user_id' => $user->id,
            'offered_item_id' => $offeredItem->id,
            'for_challenge_item_id' => $currentItem->id,
            'message' => $validated['message'] ?? null,
            'status' => OfferStatus::Pending,
        ]);
    }
}
