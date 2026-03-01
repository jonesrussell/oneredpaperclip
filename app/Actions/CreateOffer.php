<?php

namespace App\Actions;

use App\Enums\ItemRole;
use App\Enums\OfferStatus;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Media;
use App\Models\Offer;
use App\Models\User;
use App\Notifications\OfferReceivedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateOffer
{
    /**
     * Create an offer: create the offered item, then the offer targeting the challenge's current item.
     *
     * @param  array{offered_item: array{title: string, description?: string|null, image?: UploadedFile|null}, message?: string|null}  $validated
     */
    public function __invoke(array $validated, Challenge $challenge, User $user): Offer
    {
        $currentItem = $challenge->currentItem;
        if (! $currentItem) {
            throw new \InvalidArgumentException('Challenge has no current item.');
        }

        $offer = DB::transaction(function () use ($validated, $challenge, $user, $currentItem) {
            $offeredItem = Item::create([
                'itemable_type' => User::class,
                'itemable_id' => $user->id,
                'role' => ItemRole::Offered->value,
                'title' => $validated['offered_item']['title'],
                'description' => $validated['offered_item']['description'] ?? null,
            ]);

            if (($image = $validated['offered_item']['image'] ?? null) instanceof UploadedFile) {
                $path = $image->store('items/'.$offeredItem->id, 'public');
                if ($path === false) {
                    throw new \RuntimeException("Failed to store uploaded image for item {$offeredItem->id}.");
                }
                Media::query()->create([
                    'model_type' => Item::class,
                    'model_id' => $offeredItem->id,
                    'collection_name' => 'default',
                    'file_name' => $image->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'size' => $image->getSize() ?: 0,
                ]);
            }

            return Offer::create([
                'challenge_id' => $challenge->id,
                'from_user_id' => $user->id,
                'offered_item_id' => $offeredItem->id,
                'for_challenge_item_id' => $currentItem->id,
                'message' => $validated['message'] ?? null,
                'status' => OfferStatus::Pending,
            ]);
        });

        $offer->load(['challenge', 'fromUser', 'offeredItem']);

        if ($challenge->user) {
            try {
                $challenge->user->notify(new OfferReceivedNotification($offer));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $offer;
    }
}
