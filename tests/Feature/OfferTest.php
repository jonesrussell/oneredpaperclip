<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;

test('offer belongs to campaign and has campaign_id and campaign relation', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics',
    ]);

    $campaign = Campaign::create([
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
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'offered',
        'title' => 'Pen',
        'description' => 'A pen to trade.',
    ]);

    $forCampaignItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);

    $offer = Offer::create([
        'campaign_id' => $campaign->id,
        'from_user_id' => $user->id,
        'offered_item_id' => $offeredItem->id,
        'for_campaign_item_id' => $forCampaignItem->id,
        'message' => 'I offer my pen for your paperclip.',
        'status' => 'pending',
    ]);

    expect($offer->campaign_id)->toBe($campaign->id)
        ->and($offer->campaign)->not->toBeNull()
        ->and($offer->campaign->id)->toBe($campaign->id)
        ->and($offer->fromUser)->not->toBeNull()
        ->and($offer->fromUser->id)->toBe($user->id);
});
