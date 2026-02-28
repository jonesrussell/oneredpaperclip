<?php

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Enums\ItemRole;
use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;

uses()->group('offers');

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Active,
        'visibility' => ChallengeVisibility::Public,
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => ItemRole::Start,
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();

    $offeredItem = Item::create([
        'itemable_type' => Offer::class,
        'itemable_id' => 0,
        'role' => ItemRole::Offered,
        'title' => 'A pen',
        'description' => 'Blue ballpoint.',
    ]);
    $this->offer = Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'message' => 'I offer my pen.',
        'status' => OfferStatus::Pending,
    ]);
    $offeredItem->update(['itemable_id' => $this->offer->id]);
});

test('challenge owner can accept offer and trade is created with correct fields and offerer is notified', function () {
    $response = $this->actingAs($this->owner)->post(route('offers.accept', $this->offer));

    $response->assertRedirect()
        ->assertSessionHas('success', 'Offer accepted — trade created!');

    $trade = Trade::where('offer_id', $this->offer->id)->first();
    $challengeTradesCount = $this->challenge->trades()->count();
    expect($trade)->not->toBeNull()
        ->and($trade->position)->toBe($challengeTradesCount)
        ->and($trade->offered_item_id)->toBe($this->offer->offered_item_id)
        ->and($trade->received_item_id)->toBe($this->challenge->current_item_id)
        ->and($trade->status)->toBe(TradeStatus::PendingConfirmation)
        ->and($trade->challenge_id)->toBe($this->challenge->id);

    expect($this->offer->fresh()->status)->toBe(OfferStatus::Accepted);

    $notification = Notification::where('user_id', $this->offerer->id)
        ->where('type', 'offer_accepted')
        ->whereNull('read_at')
        ->first();
    expect($notification)->not->toBeNull()
        ->and($notification->data)->toMatchArray([
            'challenge_id' => $this->challenge->id,
            'offer_id' => $this->offer->id,
        ]);
});

test('non-owner cannot accept offer', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($other)->post(route('offers.accept', $this->offer));

    $response->assertForbidden();
    expect($this->offer->fresh()->status)->toBe(OfferStatus::Pending);
    expect(Trade::where('offer_id', $this->offer->id)->count())->toBe(0);
});

test('offer must be pending to accept', function () {
    $this->offer->update(['status' => OfferStatus::Declined]);

    $response = $this->actingAs($this->owner)->post(route('offers.accept', $this->offer));

    $response->assertForbidden();
    expect(Trade::where('offer_id', $this->offer->id)->count())->toBe(0);
});

test('cannot accept when challenge current item no longer matches offer for_challenge_item', function () {
    $otherItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $this->challenge->id,
        'role' => ItemRole::Goal,
        'title' => 'Another item',
        'description' => null,
    ]);
    $this->challenge->update(['current_item_id' => $otherItem->id]);

    $response = $this->actingAs($this->owner)->post(route('offers.accept', $this->offer));

    $response->assertForbidden();
    expect(Trade::where('offer_id', $this->offer->id)->count())->toBe(0);
});
