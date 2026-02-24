<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemMediaRequest;
use App\Models\Item;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;

class ItemMediaController extends Controller
{
    /**
     * Store an image for the item. Replaces any existing media (one image per item).
     */
    public function store(StoreItemMediaRequest $request, Item $item): RedirectResponse
    {
        $file = $request->file('image');
        $path = $file->store('items/'.$item->id, 'public');

        $item->media()->delete();

        Media::query()->create([
            'model_type' => Item::class,
            'model_id' => $item->id,
            'collection_name' => 'default',
            'file_name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'size' => $file->getSize(),
        ]);

        return back()->with('message', 'Photo added.');
    }
}
