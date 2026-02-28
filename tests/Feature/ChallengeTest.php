<?php

use App\Models\Challenge;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;

test('challenge has start item and goal item and current_item_id set to start item', function () {
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

    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);

    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'A house',
        'description' => 'Dream house.',
    ]);

    $challenge->update([
        'current_item_id' => $startItem->id,
        'goal_item_id' => $goalItem->id,
    ]);

    $challenge->refresh();

    expect($challenge->startItem)->not->toBeNull()
        ->and($challenge->startItem->id)->toBe($startItem->id)
        ->and($challenge->startItem->role)->toBe('start')
        ->and($challenge->goalItem)->not->toBeNull()
        ->and($challenge->goalItem->id)->toBe($goalItem->id)
        ->and($challenge->goalItem->role)->toBe('goal')
        ->and($challenge->current_item_id)->toBe($startItem->id);
});
