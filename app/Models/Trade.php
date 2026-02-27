<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    /** @use HasFactory<\Database\Factories\TradeFactory> */
    use HasFactory;

    protected $fillable = [
        'challenge_id',
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
            'status' => TradeStatus::class,
            'confirmed_by_offerer_at' => 'datetime',
            'confirmed_by_owner_at' => 'datetime',
        ];
    }

    public function challenge(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Challenge::class);
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
