<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory;

    protected $fillable = [
        'challenge_id',
        'from_user_id',
        'offered_item_id',
        'for_challenge_item_id',
        'message',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function challenge(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function fromUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function offeredItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'offered_item_id');
    }

    public function forChallengeItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'for_challenge_item_id');
    }

    public function trade(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Trade::class);
    }
}
