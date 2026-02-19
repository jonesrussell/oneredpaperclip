<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;

test('campaign has start item and goal item and current_item_id set to start item', function () {
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

    $startItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);

    $goalItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'goal',
        'title' => 'A house',
        'description' => 'Dream house.',
    ]);

    $campaign->update([
        'current_item_id' => $startItem->id,
        'goal_item_id' => $goalItem->id,
    ]);

    $campaign->refresh();

    expect($campaign->startItem)->not->toBeNull()
        ->and($campaign->startItem->id)->toBe($startItem->id)
        ->and($campaign->startItem->role)->toBe('start')
        ->and($campaign->goalItem)->not->toBeNull()
        ->and($campaign->goalItem->id)->toBe($goalItem->id)
        ->and($campaign->goalItem->role)->toBe('goal')
        ->and($campaign->current_item_id)->toBe($startItem->id);
});
