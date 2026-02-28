<?php

use App\Enums\OfferStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
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
});

test('post api challenge offers store requires auth', function () {
    $response = $this->postJson(route('api.challenges.offers.store', $this->challenge), [
        'offered_item' => ['title' => 'A pen', 'description' => 'Blue pen.'],
    ]);

    $response->assertUnauthorized();
});

test('post api challenge offers store creates offer and returns json', function () {
    $response = $this->actingAs($this->offerer)->postJson(route('api.challenges.offers.store', $this->challenge), [
        'offered_item' => ['title' => 'A pen', 'description' => 'Blue ballpoint.'],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('message', 'Offer submitted.');
    $response->assertJsonStructure(['offer' => ['id', 'status']]);
    expect(Offer::where('challenge_id', $this->challenge->id)->where('from_user_id', $this->offerer->id)->count())->toBe(1);
});

test('post api offers accept requires auth', function () {
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
        'status' => OfferStatus::Pending,
    ]);

    $response = $this->postJson(route('api.offers.accept', $offer));

    $response->assertUnauthorized();
});

test('post api offers accept returns json when owner accepts', function () {
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
        'status' => OfferStatus::Pending,
    ]);

    $response = $this->actingAs($this->owner)->postJson(route('api.offers.accept', $offer));

    $response->assertOk();
    $response->assertJsonPath('message', 'Offer accepted.');
    $response->assertJsonStructure(['trade_id']);
});

test('post api offers decline returns json when owner declines', function () {
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
        'status' => OfferStatus::Pending,
    ]);

    $response = $this->actingAs($this->owner)->postJson(route('api.offers.decline', $offer));

    $response->assertOk();
    $response->assertJsonPath('message', 'Offer declined.');
});
