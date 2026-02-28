<?php

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;

test('offer belongs to challenge and has challenge_id and challenge relation', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics',
    ]);

    $challenge = Challenge::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $offeredItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'offered',
        'title' => 'Pen',
        'description' => 'A pen to trade.',
    ]);

    $forChallengeItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);

    $offer = Offer::create([
        'challenge_id' => $challenge->id,
        'from_user_id' => $user->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $forChallengeItem->id,
        'message' => 'I offer my pen for your paperclip.',
        'status' => 'pending',
    ]);

    expect($offer->challenge_id)->toBe($challenge->id)
        ->and($offer->challenge)->not->toBeNull()
        ->and($offer->challenge->id)->toBe($challenge->id)
        ->and($offer->fromUser)->not->toBeNull()
        ->and($offer->fromUser->id)->toBe($user->id);
});
