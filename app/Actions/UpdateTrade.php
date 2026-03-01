<?php

namespace App\Actions;

use App\Enums\TradeStatus;
use App\Models\Item;
use App\Models\Media;
use App\Models\Trade;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateTrade
{
    /**
     * Update the traded item's title, description, and/or image.
     * Only allowed while trade is pending confirmation.
     *
     * @param  array{title?: string, description?: string|null, image?: UploadedFile|null}  $validated
     */
    public function __invoke(Trade $trade, array $validated): Trade
    {
        if ($trade->status !== TradeStatus::PendingConfirmation) {
            throw new \InvalidArgumentException('Cannot update a completed trade.');
        }

        return DB::transaction(function () use ($trade, $validated) {
            $item = $trade->offeredItem;

            $itemData = [];
            if (array_key_exists('title', $validated)) {
                $itemData['title'] = $validated['title'];
            }
            if (array_key_exists('description', $validated)) {
                $itemData['description'] = $validated['description'];
            }

            if (! empty($itemData)) {
                $item->update($itemData);
            }

            if (($image = $validated['image'] ?? null) instanceof UploadedFile) {
                $existingMedia = $item->media()->first();
                if ($existingMedia) {
                    Storage::disk($existingMedia->disk)->delete($existingMedia->path);
                    $existingMedia->delete();
                }

                $path = $image->store('items/'.$item->id, 'public');
                if ($path === false) {
                    throw new \RuntimeException("Failed to store uploaded image for item {$item->id}.");
                }

                Media::query()->create([
                    'model_type' => Item::class,
                    'model_id' => $item->id,
                    'collection_name' => 'default',
                    'file_name' => $image->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'size' => $image->getSize() ?: 0,
                ]);
            }

            return $trade->fresh(['offeredItem.media']);
        });
    }
}
