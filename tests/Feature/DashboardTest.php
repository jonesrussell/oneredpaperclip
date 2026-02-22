<?php

use App\Enums\CampaignStatus;
use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Campaign;
use App\Models\Category;
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
        ->has('activeCampaignsCount')
        ->has('pendingOffersCount')
        ->has('completedTradesCount')
        ->where('activeCampaignsCount', 0)
        ->where('pendingOffersCount', 0)
        ->where('completedTradesCount', 0));
});

test('dashboard shows correct stats for user with campaigns, pending offers and completed trades', function () {
    $owner = User::factory()->create();
    $offerer1 = User::factory()->create();
    $offerer2 = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $campaign = Campaign::create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'status' => CampaignStatus::Active,
        'visibility' => 'public',
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $campaign->update(['current_item_id' => $startItem->id]);
    $campaign = $campaign->fresh();

    $pendingOfferedItem = Item::create([
        'itemable_type' => Offer::class,
        'itemable_id' => 0,
        'role' => 'offered',
        'title' => 'A pen',
        'description' => 'Blue ballpoint.',
    ]);
    $pendingOffer = Offer::create([
        'campaign_id' => $campaign->id,
        'from_user_id' => $offerer1->id,
        'offered_item_id' => $pendingOfferedItem->id,
        'for_campaign_item_id' => $campaign->current_item_id,
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
        'campaign_id' => $campaign->id,
        'from_user_id' => $offerer2->id,
        'offered_item_id' => $acceptedOfferedItem->id,
        'for_campaign_item_id' => $campaign->current_item_id,
        'message' => 'I offer my fish pen.',
        'status' => OfferStatus::Accepted,
    ]);
    $acceptedOfferedItem->update(['itemable_id' => $acceptedOffer->id]);

    $trade = Trade::create([
        'campaign_id' => $campaign->id,
        'offer_id' => $acceptedOffer->id,
        'position' => 1,
        'offered_item_id' => $acceptedOffer->offered_item_id,
        'received_item_id' => $campaign->current_item_id,
        'status' => TradeStatus::Completed,
        'confirmed_by_offerer_at' => now(),
        'confirmed_by_owner_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('activeCampaignsCount', 1)
        ->where('pendingOffersCount', 1)
        ->where('completedTradesCount', 1));
});
