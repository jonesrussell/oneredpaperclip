<?php

use App\Models\Campaign;
use App\Models\Item;
use App\Models\User;

uses()->group('campaigns');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('post to campaigns.store with valid start_item and goal_item creates campaign and two items', function () {
    $response = $this->actingAs($this->user)->post(route('campaigns.store'), [
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

    $campaign = Campaign::where('user_id', $this->user->id)->latest()->first();
    expect($campaign)->not->toBeNull()
        ->and($campaign->title)->toBe('Red paperclip to house')
        ->and($campaign->status->value)->toBe('draft')
        ->and($campaign->visibility->value)->toBe('public');

    $items = Item::where('itemable_type', Campaign::class)->where('itemable_id', $campaign->id)->get();
    expect($items)->toHaveCount(2);

    $startItem = $items->firstWhere('role', 'start');
    $goalItem = $items->firstWhere('role', 'goal');
    expect($startItem)->not->toBeNull()
        ->and($startItem->title)->toBe('One red paperclip')
        ->and($goalItem)->not->toBeNull()
        ->and($goalItem->title)->toBe('A house')
        ->and($campaign->current_item_id)->toBe($startItem->id)
        ->and($campaign->goal_item_id)->toBe($goalItem->id);
});

test('post to campaigns.store with status active sets campaign status to active', function () {
    $response = $this->actingAs($this->user)->post(route('campaigns.store'), [
        'title' => 'My campaign',
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

    $campaign = Campaign::where('user_id', $this->user->id)->latest()->first();
    expect($campaign)->not->toBeNull()
        ->and($campaign->status->value)->toBe('active');
});
