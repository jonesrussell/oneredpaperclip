<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'campaign_id',
        'offer_id',
        'position',
        'offered_item_id',
        'received_item_id',
        'status',
        'confirmed_by_offerer_at',
        'confirmed_by_owner_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_by_offerer_at' => 'datetime',
            'confirmed_by_owner_at' => 'datetime',
        ];
    }

    public function campaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function offer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function offeredItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'offered_item_id');
    }

    public function receivedItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'received_item_id');
    }
}
