<?php

use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;

uses()->group('trades');

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

    $this->actingAs($this->owner)->post(route('offers.accept', $this->offer));
    $this->trade = Trade::where('offer_id', $this->offer->id)->first();
});

test('offerer can confirm trade and confirmed_by_offerer_at is set', function () {
    $response = $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect();
    $this->trade->refresh();
    expect($this->trade->confirmed_by_offerer_at)->not->toBeNull()
        ->and($this->trade->confirmed_by_owner_at)->toBeNull()
        ->and($this->trade->status)->toBe(TradeStatus::PendingConfirmation);
});

test('owner can confirm trade and confirmed_by_owner_at is set', function () {
    $this->trade->update(['confirmed_by_offerer_at' => now()]);

    $response = $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect();
    $this->trade->refresh();
    expect($this->trade->confirmed_by_owner_at)->not->toBeNull();
});

test('when both confirm trade status is completed and challenge current_item_id is offered_item_id', function () {
    $this->trade->update(['confirmed_by_offerer_at' => now()]);

    $response = $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect();
    $this->trade->refresh();
    $this->challenge->refresh();
    expect($this->trade->status)->toBe(TradeStatus::Completed)
        ->and($this->trade->confirmed_by_offerer_at)->not->toBeNull()
        ->and($this->trade->confirmed_by_owner_at)->not->toBeNull()
        ->and($this->challenge->current_item_id)->toBe($this->trade->offered_item_id);
});

test('user who is neither offerer nor challenge owner cannot confirm trade', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($other)->post(route('trades.confirm', $this->trade));

    $response->assertForbidden();
    expect($this->trade->fresh()->confirmed_by_offerer_at)->toBeNull()
        ->and($this->trade->fresh()->confirmed_by_owner_at)->toBeNull();
});

test('confirm is idempotent for offerer', function () {
    $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));
    $firstConfirmedAt = $this->trade->fresh()->confirmed_by_offerer_at;

    $response = $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect();
    expect($this->trade->fresh()->confirmed_by_offerer_at->eq($firstConfirmedAt))->toBeTrue();
});

test('confirm is idempotent for owner', function () {
    $this->trade->update(['confirmed_by_offerer_at' => now()]);
    $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));
    $firstConfirmedAt = $this->trade->fresh()->confirmed_by_owner_at;

    $response = $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect();
    expect($this->trade->fresh()->confirmed_by_owner_at->eq($firstConfirmedAt))->toBeTrue();
});
