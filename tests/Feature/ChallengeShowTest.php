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

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Active,
        'visibility' => ChallengeVisibility::Public,
        'title' => 'Test challenge',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => ItemRole::Start,
        'title' => 'Paperclip',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();
});

test('show page includes offer from_user data', function () {
    $offeredItem = Item::create([
        'itemable_type' => User::class,
        'itemable_id' => $this->offerer->id,
        'role' => ItemRole::Offered,
        'title' => 'A pen',
    ]);
    Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'status' => OfferStatus::Pending,
    ]);

    $response = $this->actingAs($this->owner)->get(
        route('challenges.show', $this->challenge)
    );

    $response->assertInertia(fn ($page) => $page
        ->has('challenge.offers.0.from_user')
        ->where('challenge.offers.0.from_user.name', $this->offerer->name)
    );
});

test('show page includes trade confirmation booleans and offerer', function () {
    $offeredItem = Item::create([
        'itemable_type' => User::class,
        'itemable_id' => $this->offerer->id,
        'role' => ItemRole::Offered,
        'title' => 'A pen',
    ]);
    $offer = Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'status' => OfferStatus::Accepted,
    ]);
    Trade::create([
        'challenge_id' => $this->challenge->id,
        'offer_id' => $offer->id,
        'position' => 1,
        'offered_item_id' => $offeredItem->id,
        'received_item_id' => $this->challenge->current_item_id,
        'status' => TradeStatus::PendingConfirmation,
        'confirmed_by_owner_at' => now(),
    ]);

    $response = $this->actingAs($this->owner)->get(
        route('challenges.show', $this->challenge)
    );

    $response->assertInertia(fn ($page) => $page
        ->has('challenge.trades.0.owner_confirmed')
        ->where('challenge.trades.0.owner_confirmed', true)
        ->where('challenge.trades.0.offerer_confirmed', false)
        ->has('challenge.trades.0.offerer')
        ->where('challenge.trades.0.offerer.name', $this->offerer->name)
    );
});

test('show page provides SEO meta with og_type, description, and JSON-LD schema', function () {
    $response = $this->get(route('challenges.show', $this->challenge));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Show')
        ->where('meta.og_type', 'article')
        ->has('meta.description')
        ->has('meta.title')
        ->has('meta.schema')
        ->where('meta.schema.@type', 'Article')
        ->where('meta.schema.headline', 'Test challenge')
        ->where('meta.schema.author.@type', 'Person')
        ->where('meta.schema.author.name', $this->owner->name)
        ->has('meta.schema.datePublished')
        ->has('meta.schema.publisher')
    );
});

test('show page includes start_item distinct from current_item after trade', function () {
    $offeredItem = Item::create([
        'itemable_type' => User::class,
        'itemable_id' => $this->offerer->id,
        'role' => ItemRole::Offered,
        'title' => 'A fancy pen',
    ]);
    $offer = Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'status' => OfferStatus::Accepted,
    ]);
    Trade::create([
        'challenge_id' => $this->challenge->id,
        'offer_id' => $offer->id,
        'position' => 1,
        'offered_item_id' => $offeredItem->id,
        'received_item_id' => $this->challenge->current_item_id,
        'status' => TradeStatus::Completed,
        'confirmed_by_owner_at' => now(),
        'confirmed_by_offerer_at' => now(),
    ]);
    $this->challenge->update(['current_item_id' => $offeredItem->id]);

    $response = $this->actingAs($this->owner)->get(
        route('challenges.show', $this->challenge)
    );

    $response->assertInertia(fn ($page) => $page
        ->has('challenge.start_item')
        ->has('challenge.current_item')
        ->where('challenge.start_item.title', 'Paperclip')
        ->where('challenge.current_item.title', 'A fancy pen')
    );

    $data = $response->viewData('page')['props'];
    expect($data['challenge']['start_item']['id'])
        ->not->toBe($data['challenge']['current_item']['id']);
});
