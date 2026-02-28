<?php

use App\Enums\OfferStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\User;

uses()->group('offers');

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
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
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();

    $offeredItem = Item::create([
        'itemable_type' => Offer::class,
        'itemable_id' => 0,
        'role' => 'offered',
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

test('challenge owner can decline offer and offerer is notified', function () {
    $response = $this->actingAs($this->owner)->post(route('offers.decline', $this->offer));

    $response->assertRedirect();

    expect($this->offer->fresh()->status)->toBe(OfferStatus::Declined);

    $notification = Notification::where('user_id', $this->offerer->id)
        ->where('type', 'offer_declined')
        ->whereNull('read_at')
        ->first();
    expect($notification)->not->toBeNull()
        ->and($notification->data)->toMatchArray([
            'challenge_id' => $this->challenge->id,
            'offer_id' => $this->offer->id,
        ]);
});

test('non-owner cannot decline offer', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($other)->post(route('offers.decline', $this->offer));

    $response->assertForbidden();
    expect($this->offer->fresh()->status)->toBe(OfferStatus::Pending);
});

test('offer must be pending to decline', function () {
    $this->offer->update(['status' => OfferStatus::Accepted]);

    $response = $this->actingAs($this->owner)->post(route('offers.decline', $this->offer));

    $response->assertForbidden();
    expect($this->offer->fresh()->status)->toBe(OfferStatus::Accepted);
});
