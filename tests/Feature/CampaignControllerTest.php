<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;

uses()->group('campaigns');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('get campaign create page requires auth', function () {
    $response = $this->get(route('campaigns.create'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can get campaign create page', function () {
    Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Category::create(['name' => 'Collectibles', 'slug' => 'collectibles']);

    $response = $this->actingAs($this->user)->get(route('campaigns.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('campaigns/Create')
        ->has('categories', 2)
    );
});

test('post valid campaign redirects to campaign show', function () {
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

    $campaign = Campaign::where('user_id', $this->user->id)->latest()->first();
    $response->assertRedirect(route('campaigns.show', $campaign));
});

test('guest can get campaign show page', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $campaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $campaign->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->get(route('campaigns.show', $campaign));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('campaigns/Show')
        ->has('campaign')
        ->where('campaign.id', $campaign->id)
    );
});

test('authenticated user can get campaign show page with follow state', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $campaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $campaign->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $campaign->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->actingAs($this->user)->get(route('campaigns.show', $campaign));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('campaigns/Show')
        ->has('campaign')
        ->has('isFollowing')
    );
});

test('guest cannot get dashboard campaigns page', function () {
    $response = $this->get(route('dashboard.campaigns'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can get dashboard campaigns page', function () {
    $response = $this->actingAs($this->user)->get(route('dashboard.campaigns'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/campaigns/Index')
        ->has('campaigns')
        ->has('campaigns.data')
    );
});

test('dashboard campaigns page shows only current user campaigns', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $myCampaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $otherUser = User::factory()->create();
    $otherCampaign = Campaign::create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Other user campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->actingAs($this->user)->get(route('dashboard.campaigns'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/campaigns/Index')
        ->has('campaigns.data', 1)
        ->where('campaigns.data.0.id', $myCampaign->id)
    );
});

test('campaigns index does not include draft campaigns', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $activeCampaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Active campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $draftCampaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'visibility' => 'public',
        'title' => 'Draft campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->get(route('campaigns.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('campaigns/Index')
        ->has('campaigns.data', 1)
        ->where('campaigns.data.0.id', $activeCampaign->id)
    );
});

test('guest gets 404 when viewing draft campaign', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $campaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'visibility' => 'public',
        'title' => 'Draft campaign',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->get(route('campaigns.show', $campaign));

    $response->assertNotFound();
});

test('owner can view their own draft campaign', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $campaign = Campaign::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'visibility' => 'public',
        'title' => 'My draft',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->actingAs($this->user)->get(route('campaigns.show', $campaign));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('campaigns/Show')
        ->where('campaign.id', $campaign->id)
        ->where('campaign.title', 'My draft')
    );
});
