<?php

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Enums\ItemRole;
use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;
use App\Notifications\TradeCompletedNotification;
use App\Notifications\TradePendingConfirmationNotification;
use Illuminate\Support\Facades\Notification;

uses()->group('trades');

beforeEach(function () {
    Notification::fake();

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

    $this->actingAs($this->owner)->post(route('offers.accept', $this->offer));
    $this->trade = Trade::where('offer_id', $this->offer->id)->first();
});

test('offerer can confirm trade and confirmed_by_offerer_at is set', function () {
    $response = $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect()
        ->assertSessionHas('success', 'Trade confirmed! Waiting for the other party.');
    $this->trade->refresh();
    expect($this->trade->confirmed_by_offerer_at)->not->toBeNull()
        ->and($this->trade->confirmed_by_owner_at)->toBeNull()
        ->and($this->trade->status)->toBe(TradeStatus::PendingConfirmation);
});

test('owner can auto-complete trade without offerer confirmation', function () {
    $response = $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect()
        ->assertSessionHas('success', 'Trade complete!');
    $this->trade->refresh();
    $this->challenge->refresh();
    expect($this->trade->confirmed_by_owner_at)->not->toBeNull()
        ->and($this->trade->confirmed_by_offerer_at)->not->toBeNull()
        ->and($this->trade->status)->toBe(TradeStatus::Completed)
        ->and($this->challenge->current_item_id)->toBe($this->trade->offered_item_id);
});

test('when offerer confirms first then owner confirms trade is completed', function () {
    $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));

    $response = $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect()
        ->assertSessionHas('success', 'Trade complete!');
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
    $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));
    $firstConfirmedAt = $this->trade->fresh()->confirmed_by_owner_at;

    $response = $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    $response->assertRedirect();
    expect($this->trade->fresh()->confirmed_by_owner_at->eq($firstConfirmedAt))->toBeTrue();
});

test('offerer confirmation sends TradePendingConfirmationNotification to owner', function () {
    $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));

    Notification::assertSentTo($this->owner, TradePendingConfirmationNotification::class);
    Notification::assertNotSentTo($this->offerer, TradePendingConfirmationNotification::class);
});

test('owner auto-complete sends TradeCompletedNotification to both parties', function () {
    $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    Notification::assertSentTo($this->owner, TradeCompletedNotification::class);
    Notification::assertSentTo($this->offerer, TradeCompletedNotification::class);
    Notification::assertNotSentTo($this->owner, TradePendingConfirmationNotification::class);
    Notification::assertNotSentTo($this->offerer, TradePendingConfirmationNotification::class);
});

test('offerer then owner confirm sends TradeCompletedNotification to both', function () {
    $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));
    Notification::assertSentTo($this->owner, TradePendingConfirmationNotification::class);

    $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    Notification::assertSentTo($this->owner, TradeCompletedNotification::class);
    Notification::assertSentTo($this->offerer, TradeCompletedNotification::class);
});
