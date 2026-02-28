<?php

use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;

uses()->group('challenges');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('post to challenges.store with valid start_item and goal_item creates challenge and two items', function () {
    $response = $this->actingAs($this->user)->post(route('challenges.store'), [
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'start_item' => [
            'title' => 'One red paperclip',
            'description' => 'A single red paperclip.',
        ],
        'goal_item' => [
            'title' => 'A house',
            'description' => 'Dream house.',
        ],
    ]);

    $response->assertRedirect();

    $challenge = Challenge::where('user_id', $this->user->id)->latest()->first();
    expect($challenge)->not->toBeNull()
        ->and($challenge->title)->toBe('Red paperclip to house')
        ->and($challenge->status->value)->toBe('draft')
        ->and($challenge->visibility->value)->toBe('public');

    $items = Item::where('itemable_type', Challenge::class)->where('itemable_id', $challenge->id)->get();
    expect($items)->toHaveCount(2);

    $startItem = $items->firstWhere('role', 'start');
    $goalItem = $items->firstWhere('role', 'goal');
    expect($startItem)->not->toBeNull()
        ->and($startItem->title)->toBe('One red paperclip')
        ->and($goalItem)->not->toBeNull()
        ->and($goalItem->title)->toBe('A house')
        ->and($challenge->current_item_id)->toBe($startItem->id)
        ->and($challenge->goal_item_id)->toBe($goalItem->id);
});

test('post to challenges.store with status active sets challenge status to active', function () {
    $response = $this->actingAs($this->user)->post(route('challenges.store'), [
        'title' => 'My challenge',
        'status' => 'active',
        'start_item' => [
            'title' => 'Start item',
            'description' => null,
        ],
        'goal_item' => [
            'title' => 'Goal item',
            'description' => null,
        ],
    ]);

    $response->assertRedirect();

    $challenge = Challenge::where('user_id', $this->user->id)->latest()->first();
    expect($challenge)->not->toBeNull()
        ->and($challenge->status->value)->toBe('active');
});
