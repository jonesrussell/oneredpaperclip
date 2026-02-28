<?php

use App\Enums\ChallengeStatus;
use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('activeChallengesCount')
        ->has('pendingOffersCount')
        ->has('completedTradesCount')
        ->where('activeChallengesCount', 0)
        ->where('pendingOffersCount', 0)
        ->where('completedTradesCount', 0));
});

test('dashboard shows correct stats for user with challenges, pending offers and completed trades', function () {
    $owner = User::factory()->create();
    $offerer1 = User::factory()->create();
    $offerer2 = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $challenge = Challenge::create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Active,
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
    $challenge = $challenge->fresh();

    $pendingOfferedItem = Item::create([
        'itemable_type' => Offer::class,
        'itemable_id' => 0,
        'role' => 'offered',
        'title' => 'A pen',
        'description' => 'Blue ballpoint.',
    ]);
    $pendingOffer = Offer::create([
        'challenge_id' => $challenge->id,
        'from_user_id' => $offerer1->id,
        'offered_item_id' => $pendingOfferedItem->id,
        'for_challenge_item_id' => $challenge->current_item_id,
        'message' => 'I offer my pen.',
        'status' => OfferStatus::Pending,
    ]);
    $pendingOfferedItem->update(['itemable_id' => $pendingOffer->id]);

    $acceptedOfferedItem = Item::create([
        'itemable_type' => Offer::class,
        'itemable_id' => 0,
        'role' => 'offered',
        'title' => 'A fish pen',
        'description' => 'Fish-shaped pen.',
    ]);
    $acceptedOffer = Offer::create([
        'challenge_id' => $challenge->id,
        'from_user_id' => $offerer2->id,
        'offered_item_id' => $acceptedOfferedItem->id,
        'for_challenge_item_id' => $challenge->current_item_id,
        'message' => 'I offer my fish pen.',
        'status' => OfferStatus::Accepted,
    ]);
    $acceptedOfferedItem->update(['itemable_id' => $acceptedOffer->id]);

    $trade = Trade::create([
        'challenge_id' => $challenge->id,
        'offer_id' => $acceptedOffer->id,
        'position' => 1,
        'offered_item_id' => $acceptedOffer->offered_item_id,
        'received_item_id' => $challenge->current_item_id,
        'status' => TradeStatus::Completed,
        'confirmed_by_offerer_at' => now(),
        'confirmed_by_owner_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('activeChallengesCount', 1)
        ->where('pendingOffersCount', 1)
        ->where('completedTradesCount', 1));
});
