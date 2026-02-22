<?php

namespace App\Actions;

use App\Enums\ItemRole;
use App\Enums\OfferStatus;
use App\Models\Campaign;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;

class CreateOffer
{
    /**
     * Create an offer: create the offered item, then the offer targeting the campaign's current item.
     *
     * @param  array{offered_item: array{title: string, description?: string|null}, message?: string|null}  $validated
     */
    public function __invoke(array $validated, Campaign $campaign, User $user): Offer
    {
        $currentItem = $campaign->currentItem;
        if (! $currentItem) {
            throw new \InvalidArgumentException('Campaign has no current item.');
        }

        $offeredItem = Item::create([
            'itemable_type' => User::class,
            'itemable_id' => $user->id,
            'role' => ItemRole::Offered->value,
            'title' => $validated['offered_item']['title'],
            'description' => $validated['offered_item']['description'] ?? null,
        ]);

        return Offer::create([
            'campaign_id' => $campaign->id,
            'from_user_id' => $user->id,
            'offered_item_id' => $offeredItem->id,
            'for_campaign_item_id' => $currentItem->id,
            'message' => $validated['message'] ?? null,
            'status' => OfferStatus::Pending,
        ]);
    }
}
