<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    protected $fillable = [
        'itemable_type',
        'itemable_id',
        'role',
        'title',
        'description',
    ];

    protected $appends = ['image_url'];

    public function itemable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function media(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * Public URL for the first media file (e.g. item photo). Eager load
     * 'media' to avoid N+1 when serializing many items.
     */
    public function getImageUrlAttribute(): ?string
    {
        $this->loadMissing('media');
        $first = $this->media->first();

        if (! $first) {
            return null;
        }

        return Storage::disk($first->disk)->url($first->path);
    }
}
