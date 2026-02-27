<?php

namespace App\Models;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challenge extends Model
{
    /** @use HasFactory<\Database\Factories\ChallengeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'visibility',
        'title',
        'story',
        'current_item_id',
        'goal_item_id',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Item::class, 'itemable');
    }

    public function currentItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'current_item_id');
    }

    public function goalItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'goal_item_id');
    }

    public function startItem(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Item::class, 'itemable')->where('role', 'start');
    }

    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function trades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    protected function casts(): array
    {
        return [
            'status' => ChallengeStatus::class,
            'visibility' => ChallengeVisibility::class,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ChallengeStatus::Active);
    }

    public function scopeNotDraft(Builder $query): Builder
    {
        return $query->where('status', '!=', ChallengeStatus::Draft);
    }

    public function scopePublicVisibility(Builder $query): Builder
    {
        return $query->where('visibility', ChallengeVisibility::Public);
    }
}
