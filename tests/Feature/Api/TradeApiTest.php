<?php

use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;

uses()->group('api', 'webmcp');

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Red paperclip to house',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $this->challenge->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => null,
    ]);
    $this->challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $this->challenge->fresh();

    $offeredItem = Item::create([
        'itemable_type' => User::class,
        'itemable_id' => $this->offerer->id,
        'role' => 'offered',
        'title' => 'Pen',
        'description' => null,
    ]);
    $offer = Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'message' => null,
        'status' => OfferStatus::Accepted,
    ]);
    $this->trade = Trade::create([
        'challenge_id' => $this->challenge->id,
        'offer_id' => $offer->id,
        'position' => 1,
        'offered_item_id' => $offeredItem->id,
        'received_item_id' => $startItem->id,
        'status' => TradeStatus::PendingConfirmation,
    ]);
});

test('post api trades confirm requires auth', function () {
    $response = $this->postJson(route('api.trades.confirm', $this->trade));

    $response->assertUnauthorized();
});

test('post api trades confirm returns json when authorized user confirms', function () {
    $response = $this->actingAs($this->owner)->postJson(route('api.trades.confirm', $this->trade));

    $response->assertOk();
    $response->assertJsonPath('message', 'Trade confirmed.');
    $response->assertJsonStructure(['trade_id', 'status']);
});
